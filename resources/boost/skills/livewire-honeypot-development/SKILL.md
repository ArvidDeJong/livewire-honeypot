---
name: livewire-honeypot-development
description: Work with darvis/livewire-honeypot. Use it to add honeypot spam protection to a Livewire or controller form, test protected forms, and react to blocked submissions.
---

# darvis/livewire-honeypot development

## When to use this skill

Use this skill when you add spam protection to a form in an application that has `darvis/livewire-honeypot` installed, when a protected form fails in tests, or when you want to log blocked submissions.

## How a submission is checked

`HoneypotService::check()` runs three checks in this order and stops at the first failure:

| Check | Fails when | Message key | `SpamBlocked` reason |
| --- | --- | --- | --- |
| Bait field | not submitted, or not empty | `spam_detected` | `FIELD_FILLED` |
| Payload | start time missing or not positive, token shorter than `token_min_length` | `spam_detected` | `INVALID_PAYLOAD` |
| Time trap | fewer than `minimum_fill_seconds` since the start time | `submitted_too_quickly` | `SUBMITTED_TOO_QUICKLY` |

Every failure dispatches `SpamBlocked` and throws a `ValidationException`.

## Livewire

- `HasHoneypot` fills the fields in `mountHasHoneypot()`. Call `resetHoneypot()` after a successful submit so the timer restarts.
- The start time and token are locked properties and live only in the component snapshot. A client that tries to change them gets `CannotUpdateLockedPropertyException`.
- `<x-honeypot />` binds `hp_website`. Pass `wire:model="form.hp_website"` to bind elsewhere and `error-key="..."` if the error lives under another key.

## Controllers

```php
use Darvis\LivewireHoneypot\Services\HoneypotService;

public function create(HoneypotService $honeypot)
{
    return view('contact', ['honeypot' => $honeypot->generate()]);
}

public function store(Request $request, HoneypotService $honeypot)
{
    $honeypot->validate($request->all());
}
```

Render every key of `$honeypot` as an input. The bait key follows `field_name`; hide that input. In this mode the start time comes from the client, so the time trap only stops naive bots. Add rate limiting to the route.

## Testing

```php
$component = Livewire::test(ContactForm::class)->set('name', 'Jane');

$this->travel(10)->seconds();

$component->call('submit')->assertHasNoErrors();
```

Or set `config(['livewire-honeypot.minimum_fill_seconds' => 0])`. Use `Event::fake([SpamBlocked::class])` to assert a submission was blocked.
