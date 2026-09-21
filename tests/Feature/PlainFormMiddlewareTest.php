<?php

use Darvis\LivewireHoneypot\Services\HoneypotService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

/*
 * These tests keep the global middleware on. A default Laravel application runs
 * ConvertEmptyStringsToNull, which turns the empty bait field into null before the controller
 * sees it. The other plain form tests use withoutMiddleware() and never meet that null.
 */
beforeEach(function () {
    Route::post('/contact', function (Request $request, HoneypotService $honeypot) {
        $honeypot->validate($request->all());

        return 'sent';
    })->middleware('web');
});

/**
 * @return array{token: string, bait: string}
 */
function renderedPlainForm(): array
{
    $html = Blade::render('<form><x-honeypot /></form>');

    preg_match('/name="hp_token" value="([^"]+)"/', $html, $token);
    preg_match('/<input type="text"\s+name="([^"]+)"/', $html, $bait);

    return ['token' => $token[1], 'bait' => $bait[1]];
}

test('the application runs the middleware that turns empty strings into null', function () {
    expect(app(Kernel::class)->hasMiddleware(ConvertEmptyStringsToNull::class))->toBeTrue();
});

test('an empty bait passes through the real middleware stack', function () {
    ['token' => $token, 'bait' => $bait] = renderedPlainForm();

    $this->travel(10)->seconds();

    $this->post('/contact', ['hp_token' => $token, $bait => '', 'email' => 'jane@example.com'])
        ->assertOk()
        ->assertSee('sent');
});

test('an empty bait under the configured field name passes through the real middleware stack', function () {
    $fields = app(HoneypotService::class)->generate();

    $this->travel(10)->seconds();

    $this->post('/contact', [...$fields, 'email' => 'jane@example.com'])
        ->assertOk()
        ->assertSee('sent');
});

test('a filled bait is blocked through the real middleware stack', function () {
    ['token' => $token, 'bait' => $bait] = renderedPlainForm();

    $this->travel(10)->seconds();

    $this->post('/contact', ['hp_token' => $token, $bait => 'spam'])
        ->assertSessionHasErrors(['hp_website' => 'Spam detected.']);
});

test('a missing bait is blocked through the real middleware stack', function () {
    ['token' => $token] = renderedPlainForm();

    $this->travel(10)->seconds();

    $this->post('/contact', ['hp_token' => $token])
        ->assertSessionHasErrors(['hp_website' => 'Spam detected.']);
});

test('a quick submit is blocked through the real middleware stack', function () {
    ['token' => $token, 'bait' => $bait] = renderedPlainForm();

    $this->post('/contact', ['hp_token' => $token, $bait => ''])
        ->assertSessionHasErrors(['hp_website' => 'Form submitted too quickly.']);
});

test('an expired token is blocked through the real middleware stack', function () {
    ['token' => $token, 'bait' => $bait] = renderedPlainForm();

    $this->travel(2)->days();

    $this->post('/contact', ['hp_token' => $token, $bait => ''])
        ->assertSessionHasErrors(['hp_website' => 'This form has expired. Please try again.']);
});

test('a JSON request gets a 422 through the real middleware stack', function () {
    ['token' => $token, 'bait' => $bait] = renderedPlainForm();

    $this->travel(10)->seconds();

    $this->postJson('/contact', ['hp_token' => $token, $bait => ''])->assertOk();

    $this->postJson('/contact', ['hp_token' => $token, $bait => 'spam'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hp_website' => 'Spam detected.']);
});
