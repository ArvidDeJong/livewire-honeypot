# darvis/livewire-honeypot

[![Latest version](https://img.shields.io/packagist/v/darvis/livewire-honeypot.svg)](https://packagist.org/packages/darvis/livewire-honeypot)
[![Tests](https://github.com/ArvidDeJong/livewire-honeypot/actions/workflows/tests.yml/badge.svg)](https://github.com/ArvidDeJong/livewire-honeypot/actions/workflows/tests.yml)
[![PHP version](https://img.shields.io/packagist/dependency-v/darvis/livewire-honeypot/php.svg)](https://packagist.org/packages/darvis/livewire-honeypot)
[![License](https://img.shields.io/packagist/l/darvis/livewire-honeypot.svg)](LICENSE)

`darvis/livewire-honeypot` stops automated form spam in **Livewire** components and plain **Laravel** forms without a CAPTCHA. It adds a hidden bait field that only bots fill in, and refuses a form that is submitted within a few seconds of loading. No cookies, no JavaScript, no third-party service.

![A contact form as a visitor sees it, next to the same form as a bot sees it with the hidden field revealed](https://arviddejong.github.io/livewire-honeypot/assets/images/visitor-vs-bot.png)

## Features

- Hidden bait field with a generated name such as `referral_3f9a`, a neutral label and ignore attributes for password managers, so browser autofill does not block real visitors
- Time trap: a minimum time between loading and submitting, 5 seconds by default
- A start time the client cannot change: locked properties in Livewire, a token signed with `APP_KEY` in plain forms
- One Blade component, `<x-honeypot />`, for Livewire and plain forms; it also shows the error message
- Livewire class components, single-file and multi-file components, and form objects
- `SpamBlocked` event to log or count blocked submissions
- Works with a strict Content Security Policy through a nonce
- English, Dutch, German, French and Spanish messages

## Requirements

- PHP 8.2 or higher
- Laravel 11, 12 or 13
- Livewire 3 or 4 (Composer installs it with the package, also when you only protect plain forms)

## Installation

```bash
composer require darvis/livewire-honeypot
```

There is nothing to publish and no migration to run. The application needs an `APP_KEY`. See [Installation](https://arviddejong.github.io/livewire-honeypot/installation.html) for a way to check that it works.

## Environment variables

Everything works without extra settings. These are the variables you are most likely to use:

| Variable | Default | When to use it |
| --- | --- | --- |
| `HONEYPOT_MINIMUM_FILL_SECONDS` | `5` | Seconds a visitor needs before a form may be submitted. Lower it for a one field form such as a newsletter signup, raise it for long forms. `0` turns the time check off; the hidden field still works |
| `HONEYPOT_MAXIMUM_FILL_SECONDS` | `86400` | Plain forms loaded longer ago than this are rejected as expired, so a scraped token can't be replayed. `0` turns expiry off. Livewire forms never expire |
| `HONEYPOT_FIELD_NAME` | `hp_website` | Plain forms only: the key their error is reported under. Change it when that key clashes with a field of your own |
| `HONEYPOT_TOKEN_LENGTH` | `24` | Length of the random part of a plain form token. Rarely needed |
| `APP_KEY` | | Laravel's own key. Must be set: the package signs its tokens with it |
| `APP_PREVIOUS_KEYS` | | Laravel's own setting. When you rotate `APP_KEY`, put the old key here so forms that are already open still validate |

A typical `.env` for a short form:

```dotenv
HONEYPOT_MINIMUM_FILL_SECONDS=3
HONEYPOT_MAXIMUM_FILL_SECONDS=3600
```

Run `php artisan config:clear` after a change, or `php artisan config:cache` again on the server. See [Configuration and events](https://arviddejong.github.io/livewire-honeypot/configuration.html) for the config file itself.

## Quick start

`app/Livewire/ContactForm.php`:

```php
<?php

namespace App\Livewire;

use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Livewire\Component;

class ContactForm extends Component
{
    use HasHoneypot;

    public string $email = '';

    public function submit(): void
    {
        $this->validate(['email' => 'required|email']);
        $this->validateHoneypot();

        // Process the form here.

        $this->reset('email');
        $this->resetHoneypot();
    }
}
```

`resources/views/livewire/contact-form.blade.php`:

```blade
<form wire:submit="submit">
    <input type="email" wire:model="email">
    <x-honeypot />
    <button type="submit">Send</button>
</form>
```

Submit within five seconds, or with the hidden field filled in, and the form shows a validation error instead of running the rest of `submit()`. A form that posts to a controller works too: see [Plain forms and controllers](https://arviddejong.github.io/livewire-honeypot/plain-forms.html).

## Documentation

Full documentation: **https://arviddejong.github.io/livewire-honeypot/**

- [Installation](docs/installation.md): requirements, the steps, and how to check that it works
- [Livewire forms](docs/livewire.md): a complete contact form, single-file and multi-file components, form objects
- [Plain forms and controllers](docs/plain-forms.md): a Blade form with a controller, expiry, key rotation, JavaScript forms
- [Configuration and events](docs/configuration.md): the settings, translations, Content Security Policy, the `SpamBlocked` event
- [Testing your forms](docs/testing.md): test a protected component or controller in your application
- [How it works](docs/how-it-works.md): the checks, why the field is hidden this way, and what a honeypot does not stop
- [Troubleshooting](docs/troubleshooting.md): real visitors are blocked, nothing is blocked, and every error message
- [Compared to alternatives](docs/comparison.md): spatie/laravel-honeypot and CAPTCHA services
- [Your honeypot may be blocking real visitors](docs/autofill-blocks-real-visitors.md): the autofill problem
- [Honeypot autofill test](https://arviddejong.github.io/livewire-honeypot/honeypot-autofill-test.html): test your browser and check your own form
- [FAQ](https://arviddejong.github.io/livewire-honeypot/faq.html): short answers

## Laravel Boost

The package ships a [Laravel Boost](https://github.com/laravel/boost) guideline and skill. Run `php artisan boost:install`, or `php artisan boost:update --discover` in a project that already uses Boost.

## Testing

```bash
composer test      # Pest
composer lint      # Pint, check only; composer format fixes
composer analyse   # Larastan
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

Found a way around the honeypot? Report it privately, as described in [SECURITY.md](SECURITY.md).

## License

MIT. See [LICENSE](LICENSE).
