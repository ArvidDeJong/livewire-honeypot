<?php

use Darvis\LivewireHoneypot\Services\HoneypotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    Route::post('/contact', function (Request $request, HoneypotService $honeypot) {
        $honeypot->validate($request->all());

        return 'sent';
    })->middleware('web');
});

function renderPlainHoneypot(): array
{
    $html = Blade::render('<form><x-honeypot /></form>');

    preg_match('/name="hp_token" value="([^"]+)"/', $html, $token);
    preg_match('/<input type="text"\s+name="([^"]+)"/', $html, $bait);

    return ['html' => $html, 'token' => $token[1] ?? null, 'bait' => $bait[1] ?? null];
}

test('it renders a signed token and a generated bait name outside Livewire', function () {
    ['html' => $html, 'token' => $token, 'bait' => $bait] = renderPlainHoneypot();

    $service = app(HoneypotService::class);

    expect($service->startedAtFromToken($token))->toBe(now()->getTimestamp());
    expect($bait)->toBe($service->baitName($token));
    expect($html)->not->toContain('wire:model')
        ->not->toContain('hp_started_at')
        ->toContain('autocomplete="off"')
        ->toContain('data-1p-ignore');
});

test('it shows a validation error from the session', function () {
    $errors = (new ViewErrorBag)->put('default', new MessageBag(['hp_website' => 'Spam detected.']));
    view()->share('errors', $errors);

    expect(renderPlainHoneypot()['html'])->toContain('<p class="hp-error" role="alert">Spam detected.</p>');
});

test('a plain form built with the component passes after the minimum fill time', function () {
    ['token' => $token, 'bait' => $bait] = renderPlainHoneypot();

    $this->travel(10)->seconds();

    $this->withoutMiddleware()
        ->post('/contact', ['hp_token' => $token, $bait => ''])
        ->assertOk()
        ->assertSee('sent');
});

test('a plain form built with the component rejects a filled bait', function () {
    ['token' => $token, 'bait' => $bait] = renderPlainHoneypot();

    $this->travel(10)->seconds();

    $this->withoutMiddleware()
        ->postJson('/contact', ['hp_token' => $token, $bait => 'spam'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('hp_website');
});

test('a plain form built with the component rejects a quick submit', function () {
    ['token' => $token, 'bait' => $bait] = renderPlainHoneypot();

    $this->withoutMiddleware()
        ->postJson('/contact', ['hp_token' => $token, $bait => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hp_website' => 'Form submitted too quickly.']);
});
