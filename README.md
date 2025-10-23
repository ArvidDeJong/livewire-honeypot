# darvis/livewire-honeypot

Lightweight **honeypot + time‑trap** protection for **Livewire 3** (Laravel 11).  
Blocks simple bots without CAPTCHAs, privacy‑friendly and unobtrusive.

## Features
- 🪤 Honeypot bait field (`present|size:0`)
- ⏱️ Time‑trap (minimum fill time)
- 🧩 Works as **Trait** for Livewire and as **Service** for controllers/APIs
- 🧱 Blade component `<x-honeypot />` for easy inclusion
- 🔌 Zero dependencies beyond Livewire 3 / Laravel 11

## Installation

```bash
composer require darvis/livewire-honeypot
```

(For local development, you can add a `path` repository in your app's `composer.json`.)

## Usage — Livewire (Trait)

1) In your Livewire component:

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

        $this->reset(['name','email','message']);
        $this->resetHoneypot();
    }
}
```

2) In your Blade (or Flux) view, add the component (place anywhere inside the form):

```blade
<x-honeypot />
```

## Usage — Controller / API (Service)

```php
use Darvis\LivewireHoneypot\Services\HoneypotService;

public function store(Request $request, HoneypotService $honeypot)
{
    $honeypot->validate($request->only('hp_website', 'hp_started_at', 'hp_token'));
    // process form ...
}
```

To generate fields server‑side (non‑Livewire forms):

```php
$hp = app(Darvis\LivewireHoneypot\Services\HoneypotService::class)->generate();
// pass $hp to your view to prefill hidden inputs
```

## Publishing the Blade view (optional)
```bash
php artisan vendor:publish --tag=livewire-honeypot-views
```

## Throttling (recommended)
Add request rate‑limiting on your form route:

```php
Route::get('/contact', \App\Livewire\ContactForm::class)->middleware('throttle:10,1');
```

## License
MIT © Arvid de Jong (info@arvid.nl)