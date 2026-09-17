# darvis/livewire-honeypot

[![Tests](https://github.com/ArvidDeJong/livewire-honeypot/actions/workflows/tests.yml/badge.svg)](https://github.com/ArvidDeJong/livewire-honeypot/actions/workflows/tests.yml)

Lightweight **honeypot + time-trap** spam protection for **Livewire** and plain Laravel forms.
Blocks simple bots without CAPTCHAs: privacy-friendly and invisible to visitors.

## Features

- 🪤 Hidden bait field with a generated name that browser autofill and password managers leave alone
- ⏱️ Time trap: a minimum time between loading and submitting (default 5 seconds)
- 🔒 The start time can't be faked: locked properties in Livewire, a signed token in plain forms
- 🧱 One Blade component, `<x-honeypot />`, for Livewire and plain forms, which also shows the error
- 🧩 Class components, single-file and multi-file components, and form objects
- 📣 `SpamBlocked` event to log or count blocked attempts
- 🌍 English and Dutch translations
- 🤖 Laravel Boost guideline and skill included

## Requirements

PHP 8.2+, Laravel 11, 12 or 13, and Livewire 3 or 4.

## Installation

```bash
composer require darvis/livewire-honeypot
```

No setup is needed. Config, translations and the view can be published when you want to change them.

## Quick start: Livewire

```php
use Darvis\LivewireHoneypot\Traits\HasHoneypot;

class ContactForm extends Component
{
    use HasHoneypot;

    public string $email = '';

    public function submit(): void
    {
        $this->validate(['email' => 'required|email']);
        $this->validateHoneypot();

        // process the form ...

        $this->reset('email');
        $this->resetHoneypot();
    }
}
```

```blade
<form wire:submit="submit">
    <input type="email" wire:model="email">
    <x-honeypot />
    <button type="submit">Send</button>
</form>
```

## Quick start: controller

```blade
<form method="POST" action="{{ route('contact.store') }}">
    @csrf
    <input type="email" name="email">
    <x-honeypot />
    <button type="submit">Send</button>
</form>
```

```php
use Darvis\LivewireHoneypot\Services\HoneypotService;

public function store(Request $request, HoneypotService $honeypot)
{
    $honeypot->validate($request->all());

    // process the form ...
}
```

## Documentation

Full documentation: **https://arviddejong.github.io/livewire-honeypot/**

- [How it works](docs/how-it-works.md): the checks, why the field is hidden this way, and what a honeypot does not stop
- [Livewire](docs/livewire.md): class, single-file and multi-file components, form objects, error display
- [Plain forms and controllers](docs/plain-forms.md): the service, rendering inputs yourself, page caching
- [Configuration, translations and events](docs/configuration.md)
- [Testing your forms](docs/testing.md)

## Development

```bash
composer test      # Pest
composer lint      # Pint
composer analyse   # Larastan
```

## License

MIT © Arvid de Jong (info@arvid.nl)
