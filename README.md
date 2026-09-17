# darvis/livewire-honeypot

[![Tests](https://github.com/ArvidDeJong/livewire-honeypot/actions/workflows/tests.yml/badge.svg)](https://github.com/ArvidDeJong/livewire-honeypot/actions/workflows/tests.yml)

Lightweight **honeypot + time-trap** spam protection for **Livewire** and Laravel forms.
Blocks simple bots without CAPTCHAs: privacy-friendly and invisible to visitors.

## Features

- 🪤 Hidden bait field that must stay empty
- ⏱️ Time trap: a minimum time between loading and submitting (default 5 seconds)
- 🔒 Start time and token are locked Livewire properties, so the client cannot tamper with them
- 🧩 Works as a **trait** for Livewire and as a **service** for controllers and APIs
- 🧱 Blade component `<x-honeypot />` that also shows the error message
- 📣 `SpamBlocked` event to log or count blocked attempts
- 🌍 English and Dutch translations
- 🤖 Laravel Boost guideline and skill included

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Livewire 3 or 4

## Installation

```bash
composer require darvis/livewire-honeypot
```

## Usage: Livewire (trait)

1. In your Livewire component:

```php
use Darvis\LivewireHoneypot\Traits\HasHoneypot;

class ContactForm extends Component
{
    use HasHoneypot;

    public string $name = '';
    public string $email = '';
    public string $message = '';

    public function submit(): void
    {
        $this->validate([
            'name' => 'required|string|min:2',
            'email' => 'required|email',
            'message' => 'required|string|min:10',
        ]);

        $this->validateHoneypot();

        // process form ...

        $this->reset(['name', 'email', 'message']);
        $this->resetHoneypot();
    }
}
```

2. In your Blade (or Flux) view, place the component anywhere inside the form:

```blade
<x-honeypot />
```

The component shows the honeypot error itself, so you don't need an `@error('hp_website')` of your own.
When the bait property lives elsewhere, pass the binding and error key:

```blade
<x-honeypot wire:model="form.hp_website" error-key="form.hp_website" />
```

## Usage: controller / API (service)

```php
use Darvis\LivewireHoneypot\Services\HoneypotService;

public function create(HoneypotService $honeypot)
{
    return view('contact', ['honeypot' => $honeypot->generate()]);
}

public function store(Request $request, HoneypotService $honeypot)
{
    $honeypot->validate($request->all());

    // process form ...
}
```

Render each value from `generate()` as an input and hide the bait field (keyed by `field_name`).
Outside Livewire the start time is sent by the browser, so the time trap only stops naive bots; combine it with rate limiting.

## Events

Every blocked submission dispatches `Darvis\LivewireHoneypot\Events\SpamBlocked` before the validation error is thrown:

```php
use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

Event::listen(function (SpamBlocked $event) {
    Log::info('Honeypot blocked a submission', [
        'reason' => $event->reason,       // SpamBlocked::FIELD_FILLED, SUBMITTED_TOO_QUICKLY or INVALID_PAYLOAD
        'ip' => $event->ip,
        'component' => $event->component, // Livewire component class, null for the service
    ]);
});
```

## Configuration

```bash
php artisan vendor:publish --tag=livewire-honeypot-config
```

| Option | Env | Default | Description |
| --- | --- | --- | --- |
| `minimum_fill_seconds` | `HONEYPOT_MINIMUM_FILL_SECONDS` | `5` | Minimum seconds before submitting; `0` disables the time trap |
| `field_name` | `HONEYPOT_FIELD_NAME` | `hp_website` | HTML name of the bait input and the key the service reads |
| `token_min_length` | `HONEYPOT_TOKEN_MIN_LENGTH` | `10` | Minimum token length accepted |
| `token_length` | `HONEYPOT_TOKEN_LENGTH` | `24` | Length of the generated token |

## Translations

```bash
php artisan vendor:publish --tag=livewire-honeypot-translations
```

Keys in `lang/vendor/livewire-honeypot/{locale}/validation.php`:

- `spam_detected`: the bait field was filled or the honeypot data is invalid
- `submitted_too_quickly`: the form was submitted too fast
- `honeypot_label`: label of the hidden bait field

## Customizing the view

```bash
php artisan vendor:publish --tag=livewire-honeypot-views
```

## Testing your forms

Travel past the minimum fill time instead of setting the locked properties:

```php
$component = Livewire::test(ContactForm::class)->set('name', 'Jane');

$this->travel(10)->seconds();

$component->call('submit')->assertHasNoErrors();
```

Or set `config(['livewire-honeypot.minimum_fill_seconds' => 0])` in tests that don't care about the time trap.

## Throttling (recommended)

```php
Route::get('/contact', \App\Livewire\ContactForm::class)->middleware('throttle:10,1');
```

## Development

```bash
composer test      # Pest
composer lint      # Pint
composer analyse   # Larastan
```

## License

MIT © Arvid de Jong (info@arvid.nl)
