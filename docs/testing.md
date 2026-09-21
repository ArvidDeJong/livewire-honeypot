---
title: "Testing your forms"
nav_order: 6
description: "Test a Livewire component or controller that uses darvis/livewire-honeypot with Pest: travel past the time check, fill the bait field and fake the event."
---

# Testing your forms

These tests run in your own application, with [Pest](https://pestphp.com) and Laravel's test helpers. Nothing leaves your machine: the package makes no external requests.

The examples test the `ContactForm` component from [Livewire forms](livewire.md) and the `ContactController` from [Plain forms and controllers](plain-forms.md).

## A Livewire component

`hp_started_at` and `hp_token` are locked properties, so `->set()` on them throws `CannotUpdateLockedPropertyException`. Let time pass instead: `$this->travel(10)->seconds()` moves Laravel's clock ten seconds ahead, past the default minimum of five.

`tests/Feature/ContactFormTest.php`:

```php
<?php

use App\Livewire\ContactForm;
use Livewire\Livewire;

it('sends the contact form', function () {
    $component = Livewire::test(ContactForm::class)
        ->set('email', 'jane@example.com')
        ->set('message', 'Hello there, this is a message.');

    $this->travel(10)->seconds();

    $component->call('submit')->assertHasNoErrors();
});

it('blocks a fast submit', function () {
    Livewire::test(ContactForm::class)
        ->set('email', 'jane@example.com')
        ->set('message', 'Hello there, this is a message.')
        ->call('submit')
        ->assertHasErrors('hp_website')
        ->assertSee('Form submitted too quickly.');
});

it('blocks a filled honeypot', function () {
    $component = Livewire::test(ContactForm::class)
        ->set('email', 'jane@example.com')
        ->set('message', 'Hello there, this is a message.')
        ->set('hp_website', 'spam');

    $this->travel(10)->seconds();

    $component->call('submit')
        ->assertHasErrors('hp_website')
        ->assertSee('Spam detected.');
});
```

The first test proves a real visitor gets through. The other two prove the honeypot is wired up: they fail when `validateHoneypot()` is missing from `submit()`.

## Turning the time check off in other tests

In tests that are about something else, set the minimum to zero. The bait check stays on.

```php
beforeEach(fn () => config(['livewire-honeypot.minimum_fill_seconds' => 0]));
```

## A controller

`generate()` returns a valid set of honeypot fields: an empty bait, a start time and a signed token. Merge them with your own form data.

`tests/Feature/ContactControllerTest.php`:

```php
<?php

use Darvis\LivewireHoneypot\Services\HoneypotService;

it('stores a contact request', function () {
    $fields = app(HoneypotService::class)->generate();

    $this->travel(10)->seconds();

    $this->post('/contact', [...$fields, 'email' => 'jane@example.com'])
        ->assertSessionHasNoErrors();
});

it('blocks a contact request with a filled bait', function () {
    $fields = app(HoneypotService::class)->generate();

    $this->travel(10)->seconds();

    $this->post('/contact', [...$fields, 'hp_website' => 'spam', 'email' => 'jane@example.com'])
        ->assertSessionHasErrors('hp_website');
});
```

When the first test fails with "Spam detected.", the controller passes `$request->all()` straight to `validate()`. See [why the `array_map()` line is there](plain-forms.md#why-the-array_map-line-is-there).

## The SpamBlocked event

`Event::fake()` catches the event so you can assert on it. The submission is still rejected.

```php
<?php

use App\Livewire\ContactForm;
use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

it('reports a blocked submission', function () {
    Event::fake([SpamBlocked::class]);

    Livewire::test(ContactForm::class)
        ->set('email', 'jane@example.com')
        ->set('message', 'Hello there, this is a message.')
        ->call('submit');

    Event::assertDispatched(
        SpamBlocked::class,
        fn (SpamBlocked $event) => $event->reason === SpamBlocked::SUBMITTED_TOO_QUICKLY,
    );
});
```
