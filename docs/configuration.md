---
title: "Configuration and events"
nav_order: 5
description: "The four settings of darvis/livewire-honeypot with env variables and defaults, the translated messages, Content Security Policy support and the SpamBlocked event."
---

# Configuration, translations and events

The package works without any configuration. Change a setting in `.env`, or publish the config file to `config/livewire-honeypot.php`:

```bash
php artisan vendor:publish --tag=livewire-honeypot-config
```

## Which settings are there

| Option | Env variable | Default | Description |
| --- | --- | --- | --- |
| `field_name` | `HONEYPOT_FIELD_NAME` | `hp_website` | Plain forms only. The key their errors are reported under, and the key `HoneypotService::validate()` reads the bait from when a form renders its own inputs. `<x-honeypot />` renders a generated name instead. Livewire forms always use `hp_website` |
| `maximum_fill_seconds` | `HONEYPOT_MAXIMUM_FILL_SECONDS` | `86400` | Plain forms older than this are rejected as expired; `0` turns expiry off. Livewire forms never expire |
| `minimum_fill_seconds` | `HONEYPOT_MINIMUM_FILL_SECONDS` | `5` | Minimum seconds between loading and submitting; `0` turns the time check off. The bait check stays on |
| `token_length` | `HONEYPOT_TOKEN_LENGTH` | `24` | Length of the random part of a generated token, at least 1 |

A changed `.env` value has no effect while the config is cached. Run `php artisan config:clear`, or `php artisan config:cache` again on the server.

`token_min_length` was removed in 1.4.0. It is ignored when a published config still contains it.

## Changing the messages

English, Dutch, German, French and Spanish are included. Laravel picks the language from the application locale. To change a text, publish the files:

```bash
php artisan vendor:publish --tag=livewire-honeypot-translations
```

They land in `lang/vendor/livewire-honeypot/{locale}/validation.php`.

| Key | English text | Used for |
| --- | --- | --- |
| `spam_detected` | Spam detected. | The bait was filled or missing, or the start time or token is invalid |
| `submitted_too_quickly` | Form submitted too quickly. | The form was submitted within `minimum_fill_seconds` |
| `form_expired` | This form has expired. Please try again. | A plain form was older than `maximum_fill_seconds` |
| `honeypot_label` | Leave this field empty | Label of the hidden field. Avoid words like "website" or "email", which trigger browser autofill |

## Content Security Policy

A Content Security Policy (CSP) is a response header that tells the browser which styles and scripts it may run. By default the bait field is hidden with an inline `style` attribute. A policy without `'unsafe-inline'` in `style-src` blocks that attribute, and the field becomes visible.

When a nonce is available, `<x-honeypot />` hides the field through a `<style nonce="...">` block instead. A nonce is a random value that your CSP header allows for one response. The component takes it from Laravel's Vite integration:

```php
use Illuminate\Support\Facades\Vite;

// In your CSP middleware
Vite::useCspNonce();
```

Or pass a nonce yourself through the `nonce` attribute. With spatie/laravel-csp 3, the nonce is `app('csp-nonce')`:

```blade
<x-honeypot :nonce="app('csp-nonce')" />
```

The class name and the style block are the same on every render, so a Livewire update never replaces the style block. Render the form with the page itself, not lazily: a Livewire update request can carry a different nonce than the page's policy.

## Changing the HTML

```bash
php artisan vendor:publish --tag=livewire-honeypot-views
```

The view lands in `resources/views/vendor/livewire-honeypot/components/honeypot.blade.php`.

A published view no longer gets fixes from package updates. After an update, compare it with `resources/views/components/honeypot.blade.php` in the package. Since 1.4.0 the view only holds markup; the values it uses (`$inLivewire`, `$token`, `$baitName`, `$errorBagKey`, `$cspNonce`, `$hiddenClass`, `$hiddenCss`) come from `Darvis\LivewireHoneypot\View\Components\Honeypot`. Publish it again if you published it before 1.4.0.

## Logging blocked submissions

`Darvis\LivewireHoneypot\Events\SpamBlocked` is dispatched right before a submission is rejected. An event is a message other code in your application can listen for.

| Property | Value |
| --- | --- |
| `reason` | `SpamBlocked::FIELD_FILLED` (`field_filled`), `SUBMITTED_TOO_QUICKLY` (`submitted_too_quickly`), `INVALID_PAYLOAD` (`invalid_payload`) or `EXPIRED` (`expired`) |
| `ip` | IP address of the request, or `null` |
| `component` | Class name of the Livewire component, or `null` for a plain form |

`app/Providers/AppServiceProvider.php`:

```php
use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

public function boot(): void
{
    Event::listen(function (SpamBlocked $event) {
        Log::info('Honeypot blocked a submission', [
            'reason' => $event->reason,
            'ip' => $event->ip,
            'component' => $event->component,
        ]);
    });
}
```

Every blocked submission now adds a line to your default log, `storage/logs/laravel.log` in a new application. The package itself writes nothing to the log.

Many `submitted_too_quickly` lines from different visitors can mean that `minimum_fill_seconds` is too high for a short form. Many `field_filled` lines that look human point to autofill; see [Troubleshooting](troubleshooting.md#real-visitors-get-spam-detected).
