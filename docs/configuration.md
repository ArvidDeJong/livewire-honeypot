---
description: Config options, translations (English, Dutch, German, French, Spanish), Content Security Policy support and the SpamBlocked event.
title: Configuration and events
nav_order: 5
---

# Configuration, translations and events

## Config

```bash
php artisan vendor:publish --tag=livewire-honeypot-config
```

| Option | Env | Default | Description |
| --- | --- | --- | --- |
| `minimum_fill_seconds` | `HONEYPOT_MINIMUM_FILL_SECONDS` | `5` | Minimum seconds between loading and submitting; `0` turns the time trap off |
| `maximum_fill_seconds` | `HONEYPOT_MAXIMUM_FILL_SECONDS` | `86400` | Plain forms older than this are rejected as expired; `0` turns expiry off. Livewire forms never expire |
| `field_name` | `HONEYPOT_FIELD_NAME` | `hp_website` | Key `HoneypotService` reads the bait from when a form renders its own inputs, and the key its errors are reported under |
| `token_length` | `HONEYPOT_TOKEN_LENGTH` | `24` | Length of the random part of a generated token |

`token_min_length` was removed in 1.4.0. It is ignored when a published config still contains it: plain form tokens are verified by their signature, and the Livewire token is locked.

## Translations

English, Dutch, German, French and Spanish are included.

```bash
php artisan vendor:publish --tag=livewire-honeypot-translations
```

Keys in `lang/vendor/livewire-honeypot/{locale}/validation.php`:

| Key | Used for |
| --- | --- |
| `spam_detected` | The bait was filled, or the start time or token is invalid |
| `submitted_too_quickly` | The form was submitted within `minimum_fill_seconds` |
| `form_expired` | A plain form was older than `maximum_fill_seconds` |
| `honeypot_label` | Label of the hidden field. Avoid words like "website" or "email", which trigger autofill |

## Content Security Policy

By default the bait field is hidden with an inline `style` attribute. A policy without `'unsafe-inline'` in `style-src` blocks that, and the field becomes visible.

When a nonce is available, `<x-honeypot />` hides the field through a `<style nonce="...">` block instead. It takes the nonce from Laravel's Vite integration:

```php
// In your CSP middleware
Vite::useCspNonce();
```

Or pass it yourself, for example the nonce from spatie/laravel-csp:

```blade
<x-honeypot :nonce="csp_nonce()" />
```

The class name and the style block are the same on every render, so Livewire updates never replace the style block. Render the form with the page itself, not lazily: a Livewire update request can carry a different nonce than the page's policy.

## View

```bash
php artisan vendor:publish --tag=livewire-honeypot-views
```

A published view no longer gets fixes from package updates. After an update, compare it with `resources/views/components/honeypot.blade.php` in the package. Since 1.4.0 the view only holds markup; the values it uses (`$inLivewire`, `$token`, `$baitName`, `$errorBagKey`, `$cspNonce`, `$hiddenClass`, `$hiddenCss`) come from `Darvis\LivewireHoneypot\View\Components\Honeypot`. Publish it again if you published it before 1.4.0.

## Events

`Darvis\LivewireHoneypot\Events\SpamBlocked` is dispatched just before a submission is rejected.

| Property | Value |
| --- | --- |
| `reason` | `SpamBlocked::FIELD_FILLED`, `SUBMITTED_TOO_QUICKLY`, `INVALID_PAYLOAD` or `EXPIRED` |
| `ip` | IP address of the request |
| `component` | Class of the Livewire component, or `null` for `HoneypotService::validate()` |

```php
use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

// In AppServiceProvider::boot()
Event::listen(function (SpamBlocked $event) {
    Log::channel('spam')->info('Honeypot blocked a submission', [
        'reason' => $event->reason,
        'ip' => $event->ip,
        'component' => $event->component,
    ]);
});
```

A visitor who gets `SUBMITTED_TOO_QUICKLY` again and again can mean that `minimum_fill_seconds` is too high for a short form.
