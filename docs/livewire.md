---
title: "Livewire forms"
nav_order: 3
description: "Protect a Livewire 3 or 4 form against spam: a complete contact form with the HasHoneypot trait, single-file and multi-file components, and form objects."
---

# Livewire forms

Three things protect a Livewire form:

1. the `HasHoneypot` trait on the component,
2. `<x-honeypot />` inside the `<form>`,
3. a call to `$this->validateHoneypot()` in the method that handles the submit.

## A complete contact form

Create the component with `php artisan make:livewire ContactForm` in Livewire 3, or `php artisan make:livewire ContactForm --class` in Livewire 4.

`app/Livewire/ContactForm.php`:

```php
<?php

namespace App\Livewire;

use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ContactForm extends Component
{
    use HasHoneypot;

    public string $email = '';

    public string $message = '';

    public bool $sent = false;

    public function submit(): void
    {
        $this->validate([
            'email' => 'required|email',
            'message' => 'required|string|min:10',
        ]);

        $this->validateHoneypot();

        // Send the mail or store the message here.

        $this->reset(['email', 'message']);
        $this->resetHoneypot();
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.contact-form');
    }
}
```

The trait adds three public properties: `hp_website` (the bait), `hp_started_at` and `hp_token`. It fills them when the component mounts, through the `mountHasHoneypot()` hook that Livewire calls by itself. You don't call it, and your own `mount()` method keeps working.

`validateHoneypot()` throws a `ValidationException`, the same way `validate()` does. Livewire stops the method there, so the code below it never runs for a blocked submission.

`resources/views/livewire/contact-form.blade.php`:

```blade
<form wire:submit="submit">
    <input type="email" wire:model="email">
    @error('email') <p>Enter a valid email address.</p> @enderror

    <textarea wire:model="message"></textarea>
    @error('message') <p>Write at least 10 characters.</p> @enderror

    <x-honeypot />

    <button type="submit">Send</button>

    @if ($sent)
        <p>Thank you for your message.</p>
    @endif
</form>
```

`<x-honeypot />` renders the hidden bait field and binds it to `hp_website`. It also shows the honeypot error, so a visitor who is blocked sees why.

Show the component on a page. `resources/views/contact.blade.php`:

```blade
<livewire:contact-form />
```

`routes/web.php`:

```php
use Illuminate\Support\Facades\Route;

Route::view('/contact', 'contact');
```

Open `/contact`, submit within five seconds, and the form shows "Form submitted too quickly.". Submit after five seconds and the message is sent.

### Why `resetHoneypot()` after a successful submit

`resetHoneypot()` empties the bait and restarts the timer. A second message from the same page then has to wait `minimum_fill_seconds` again. Without it, the first five seconds count once and every later submit passes the time check.

## Single-file component (Livewire 4)

`resources/views/components/contact-form.blade.php` or `resources/views/livewire/contact-form.blade.php`, depending on where your project keeps its components:

```blade
<?php

use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Livewire\Component;

new class extends Component {
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
};
?>

<form wire:submit="submit">
    <input type="email" wire:model="email">
    <x-honeypot />
    <button type="submit">Send</button>
</form>
```

## Multi-file component (Livewire 4)

`php artisan make:livewire contact-form --mfc` creates a folder with a PHP file and a Blade file. The trait and the `validateHoneypot()` call go in the PHP file, `<x-honeypot />` goes in the Blade file, exactly as in the single-file example.

## Form objects

A [form object](https://livewire.laravel.com/docs/forms) is a Livewire class that holds the fields of one form. Keep the trait on the component, not on the form object. Livewire does not run the mount hook on a form object, so the honeypot would never get a start time. Since 1.6.0 `validateHoneypot()` throws a `LogicException` that says so; older versions answered every submit with "Spam detected.".

`app/Livewire/Forms/ContactFormData.php`:

```php
<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ContactFormData extends Form
{
    #[Validate('required|email')]
    public string $email = '';
}
```

`app/Livewire/ContactForm.php`:

```php
<?php

namespace App\Livewire;

use App\Livewire\Forms\ContactFormData;
use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Livewire\Component;

class ContactForm extends Component
{
    use HasHoneypot;

    public ContactFormData $form;

    public function submit(): void
    {
        $this->form->validate();
        $this->validateHoneypot();

        // Process $this->form here.

        $this->form->reset();
        $this->resetHoneypot();
    }
}
```

```blade
<form wire:submit="submit">
    <input type="email" wire:model="form.email">
    <x-honeypot />
    <button type="submit">Send</button>
</form>
```

## The error message

`<x-honeypot />` shows the honeypot error below the hidden field, in `<p class="hp-error" role="alert">`. Style the `hp-error` class in your own CSS. Don't add your own `@error('hp_website')`, or the message appears twice.

The three messages a visitor can see are in [How it works](how-it-works.md).

## Binding the bait to another property

Most forms don't need this. When the bait has to live in another property, for example inside an array that holds the whole form, tell both the Blade component and `validateHoneypot()` where it is. The two arguments have the same meaning as the two attributes. They exist since 1.6.0.

`app/Livewire/ContactForm.php`:

```php
<?php

namespace App\Livewire;

use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Livewire\Component;

class ContactForm extends Component
{
    use HasHoneypot;

    /** @var array<string, string> */
    public array $contact = ['email' => '', 'hp_website' => ''];

    public function submit(): void
    {
        $this->validate(['contact.email' => 'required|email']);
        $this->validateHoneypot(model: 'contact.hp_website', errorKey: 'contact.hp_website');

        // Process $this->contact here.

        $this->contact = ['email' => '', 'hp_website' => ''];
        $this->resetHoneypot();
    }
}
```

```blade
<form wire:submit="submit">
    <input type="email" wire:model="contact.email">
    <x-honeypot wire:model="contact.hp_website" error-key="contact.hp_website" />
    <button type="submit">Send</button>
</form>
```

| Argument | Attribute on `<x-honeypot />` | Default | Meaning |
| --- | --- | --- | --- |
| `model` | `wire:model` | `hp_website` | Dot path of the property that holds the bait |
| `errorKey` | `error-key` | `hp_website` | Key the error is reported under, and the key the component shows |

Three rules:

- Pass the same value to the attribute and to the argument. With `wire:model` on the component and no `model` argument, `validateHoneypot()` still reads the empty `hp_website` and the bait check catches nothing.
- The property must exist and start as an empty string, as `'hp_website' => ''` does above. A path that does not exist reads as "not submitted", and every submit gets "Spam detected.".
- `resetHoneypot()` empties `hp_website` and restarts the timer. Empty your own bait property yourself.

### Running the check yourself

`validateHoneypot()` calls `HoneypotService::check()`. Call it directly when you need something the two arguments don't cover, such as another minimum time for one form:

```php
use Darvis\LivewireHoneypot\Services\HoneypotService;

app(HoneypotService::class)->check(
    bait: $this->contact['hp_website'],
    startedAt: $this->hp_started_at,
    token: $this->hp_token,
    errorKey: 'contact.hp_website',
    minimumSeconds: 10,
    component: static::class,
);
```

## Livewire forms don't expire

The start time is a [locked property](https://livewire.laravel.com/docs/locked): Livewire refuses a request that tries to change it. It has no maximum age, so a visitor who leaves a tab open overnight can still submit. Only [plain forms](plain-forms.md#expiry-and-key-rotation) expire.

## A plain form inside a Livewire component

`<x-honeypot />` uses the Livewire variant only when the component that renders it uses `HasHoneypot`. A plain form in a Livewire view that posts to a controller, such as a newsletter signup in a footer component, gets a signed token instead. Validate it in the controller as described in [Plain forms and controllers](plain-forms.md).

## Flux

`<x-honeypot />` renders plain HTML. It needs no Flux component and can sit between Flux fields in the same `<form>`.
