<?php

use Darvis\LivewireHoneypot\Services\HoneypotService;
use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Livewire\Component;
use Livewire\Livewire;

test('it hides the field with an inline style when there is no CSP nonce', function () {
    $html = Blade::render('<x-honeypot />');

    expect($html)->toContain('aria-hidden="true" style="position:absolute!important')
        ->not->toContain('<style');
});

test('it hides the field with a nonced style block when Vite has a CSP nonce', function () {
    Vite::useCspNonce('test-nonce-123');

    $class = app(HoneypotService::class)->wrapperClass();
    $html = Blade::render('<x-honeypot />');

    expect($html)->toContain('<style nonce="test-nonce-123">.'.$class.'{')
        ->toContain('<div class="'.$class.'" aria-hidden="true">')
        ->not->toContain('style="');
});

test('it uses a nonce passed to the component', function () {
    $html = Blade::render('<x-honeypot nonce="from-attribute" />');

    expect($html)->toContain('<style nonce="from-attribute">')
        ->not->toContain('style="');
});

test('the wrapper class is stable and does not reveal the honeypot', function () {
    $service = app(HoneypotService::class);

    expect($service->wrapperClass())->toBe($service->wrapperClass())
        ->toMatch('/^f[0-9a-f]{8}$/');
});

test('the style block is identical on every Livewire render', function () {
    Vite::useCspNonce('livewire-nonce');

    $component = Livewire::test(CspHoneypotComponent::class);
    preg_match('/<style nonce="livewire-nonce">[^<]*<\/style>/', $component->html(), $first);

    $component->call('$refresh');
    preg_match('/<style nonce="livewire-nonce">[^<]*<\/style>/', $component->html(), $second);

    expect($first[0] ?? null)->not->toBeNull()->toBe($second[0] ?? null);
});

test('every locale has the same translation keys', function () {
    $path = __DIR__.'/../../resources/lang';
    $expected = array_keys(require $path.'/en/validation.php');

    foreach (glob($path.'/*/validation.php') as $file) {
        expect(array_keys(require $file))->toEqualCanonicalizing($expected, $file);
    }

    expect(glob($path.'/*', GLOB_ONLYDIR))->toHaveCount(5);
});

test('it translates messages into German, French and Spanish', function (string $locale, string $message) {
    app()->setLocale($locale);

    expect(__('livewire-honeypot::validation.spam_detected'))->toBe($message);
})->with([
    ['de', 'Spam erkannt.'],
    ['fr', 'Spam détecté.'],
    ['es', 'Spam detectado.'],
]);

class CspHoneypotComponent extends Component
{
    use HasHoneypot;

    public function render(): string
    {
        return '<form><x-honeypot /></form>';
    }
}
