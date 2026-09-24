<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/*
 * Before 1.4.0, <x-honeypot /> was an anonymous component. A host view compiled back then keeps
 * rendering the package view directly until that host view changes or view:clear runs, so the
 * view must work without the class component too.
 */
beforeEach(function () {
    Blade::component('livewire-honeypot::components.honeypot', 'legacy-honeypot');
});

test('a view compiled for the anonymous component still renders a plain form', function () {
    $html = Blade::render('<x-legacy-honeypot />');

    expect($html)->toContain('name="hp_token"')
        ->toContain('aria-hidden="true" style="position:absolute!important');
});

test('a view compiled for the anonymous component keeps the nonce and error key attributes', function () {
    Vite::useCspNonce('ignored-nonce');
    view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag(['contact' => 'Spam detected.'])));

    $html = Blade::render('<x-legacy-honeypot nonce="from-attribute" error-key="contact" />');

    expect($html)->toContain('<style nonce="from-attribute">')
        ->toContain('<p class="hp-error" role="alert">Spam detected.</p>');
});
