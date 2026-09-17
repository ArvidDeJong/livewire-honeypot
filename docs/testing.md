---
description: "Test Livewire components and controllers protected by darvis/livewire-honeypot with Pest: time travel, disabling the time trap and faking events."
title: Testing your forms
nav_order: 6
---

# Testing your forms

## Livewire

`hp_started_at` and `hp_token` are locked, so `->set()` on them throws. Travel past the minimum fill time instead:

```php
use Livewire\Livewire;

it('sends the contact form', function () {
    $component = Livewire::test(ContactForm::class)
        ->set('email', 'jane@example.com')
        ->set('message', 'Hello there, this is a message.');

    $this->travel(10)->seconds();

    $component->call('submit')->assertHasNoErrors();
});

it('blocks a filled honeypot', function () {
    $component = Livewire::test(ContactForm::class)->set('hp_website', 'spam');

    $this->travel(10)->seconds();

    $component->call('submit')->assertHasErrors('hp_website');
});
```

In tests that don't care about the time trap, turn it off:

```php
beforeEach(fn () => config(['livewire-honeypot.minimum_fill_seconds' => 0]));
```

## Controllers

Build a valid payload with the service:

```php
use Darvis\LivewireHoneypot\Services\HoneypotService;

it('stores a contact request', function () {
    $fields = app(HoneypotService::class)->generate();

    $this->travel(10)->seconds();

    $this->post('/contact', [...$fields, 'email' => 'jane@example.com'])
        ->assertSessionHasNoErrors();
});
```

## Events

```php
use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Illuminate\Support\Facades\Event;

Event::fake([SpamBlocked::class]);

Livewire::test(ContactForm::class)->call('submit');

Event::assertDispatched(SpamBlocked::class, fn (SpamBlocked $event) => $event->reason === SpamBlocked::SUBMITTED_TOO_QUICKLY);
```
