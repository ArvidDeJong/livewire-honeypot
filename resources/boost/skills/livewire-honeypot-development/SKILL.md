---
name: livewire-honeypot-development
description: Work with darvis/livewire-honeypot. Use it to add honeypot spam protection to a Livewire or controller form, test protected forms, and react to blocked submissions.
---

# darvis/livewire-honeypot development

## When to use this skill

Use this skill when you add spam protection to a form in an application that has `darvis/livewire-honeypot` installed, when a protected form fails in tests, or when you want to log blocked submissions.

## How a submission is checked

The checks run in this order and stop at the first failure:

| Check | Fails when | Message key | `SpamBlocked` reason |
| --- | --- | --- | --- |
| Bait field | not submitted, or not empty | `spam_detected` | `FIELD_FILLED` |
| Payload | start time missing, or (plain forms) token malformed or wrongly signed | `spam_detected` | `INVALID_PAYLOAD` |
| Time trap | fewer than `minimum_fill_seconds` since the start time | `submitted_too_quickly` | `SUBMITTED_TOO_QUICKLY` |
| Expiry (plain forms only) | more than `maximum_fill_seconds` (default one day) | `form_expired` | `EXPIRED` |

Every failure dispatches `SpamBlocked` and throws a `ValidationException`.

## Livewire

- `HasHoneypot` fills the fields in `mountHasHoneypot()`. Call `resetHoneypot()` after a successful submit so the timer restarts.
- The start time and token are locked properties and live only in the component snapshot. A client that tries to change them gets `CannotUpdateLockedPropertyException`.
- `<x-honeypot />` binds `hp_website` but renders a generated `name` (such as `referral_3f9a`). The `wire:model="..."` and `error-key="..."` attributes only change the markup: `validateHoneypot()` still reads `$this->hp_website` and reports under `hp_website`. A component that binds the bait elsewhere must call `HoneypotService::check()` itself.
- With a form object, keep the trait on the component and bind the form fields as `form.*`.

## Plain forms

```blade
<form method="POST" action="/contact">
    @csrf
    <x-honeypot />
</form>
```

```php
use Darvis\LivewireHoneypot\Services\HoneypotService;

public function store(Request $request, HoneypotService $honeypot)
{
    // ConvertEmptyStringsToNull turns the empty bait into null; the service rejects null as a missing field.
    $honeypot->validate(array_map(fn ($value) => $value ?? '', $request->all()));
}
```

Never pass `$request->all()` unchanged in an application that runs Laravel's default `ConvertEmptyStringsToNull` middleware: every real submission is then rejected with "Spam detected.".

Outside Livewire the component renders a signed `hp_token` (`random.timestamp.hmac` with the app key). `validate()` takes the start time from that token and ignores a submitted `hp_started_at`. It finds the bait under the generated name, or under `field_name` for forms that render their own inputs from `generate()`. Errors go under `field_name`. A plain form inside a Livewire component that doesn't use `HasHoneypot` also gets this variant. Tokens verify against `APP_KEY` and `APP_PREVIOUS_KEYS`; without an app key the package throws `MissingAppKeyException`. Add rate limiting to the route: a token can be replayed until it expires.

## Testing

```php
$component = Livewire::test(ContactForm::class)->set('name', 'Jane');

$this->travel(10)->seconds();

$component->call('submit')->assertHasNoErrors();
```

For a controller, post `app(HoneypotService::class)->generate()` merged with the form data, after travelling in time. Or set `config(['livewire-honeypot.minimum_fill_seconds' => 0])`. Use `Event::fake([SpamBlocked::class])` to assert a submission was blocked.
