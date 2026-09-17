---
description: Add honeypot spam protection to Livewire 3 and 4 forms: class, single-file and multi-file components, form objects and error display.
title: Livewire
nav_order: 3
---

# Livewire

Add the `HasHoneypot` trait, place `<x-honeypot />` inside the form and call `validateHoneypot()` in the submit action. The trait fills the honeypot values when the component mounts.

## Class component

```php
use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Livewire\Component;

class ContactForm extends Component
{
    use HasHoneypot;

    public string $email = '';
    public string $message = '';

    public function submit(): void
    {
        $this->validate([
            'email' => 'required|email',
            'message' => 'required|string|min:10',
        ]);

        $this->validateHoneypot();

        // process the form ...

        $this->reset(['email', 'message']);
        $this->resetHoneypot();
    }
}
```

```blade
<form wire:submit="submit">
    <input type="email" wire:model="email">
    <textarea wire:model="message"></textarea>

    <x-honeypot />

    <button type="submit">Send</button>
</form>
```

Call `resetHoneypot()` after a successful submit. It restarts the timer, so a second message from the same page has to wait again.

## Single-file component (Livewire 4)

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

        // process the form ...

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

With `php artisan make:livewire contact-form --mfc`, the trait goes in the PHP file and `<x-honeypot />` in the Blade file, just like the single-file example.

## Form objects

Keep the trait on the component, not on the form object. The honeypot values are not form data, and `#[Locked]` and the mount hook belong to the component.

```php
class ContactForm extends Component
{
    use HasHoneypot;

    public ContactFormData $form;

    public function submit(): void
    {
        $this->form->validate();
        $this->validateHoneypot();

        // process $this->form ...

        $this->form->reset();
        $this->resetHoneypot();
    }
}
```

```blade
<form wire:submit="submit">
    <input type="email" wire:model="form.email">
    <x-honeypot />
</form>
```

## Showing the error

`<x-honeypot />` shows the honeypot error below the hidden field, styled through the `hp-error` class. Don't add your own `@error('hp_website')`, or the message appears twice. When the bait property lives somewhere else, pass the binding and the error key:

```blade
<x-honeypot wire:model="contact.hp_website" error-key="contact.hp_website" />
```

## Flux

`<x-honeypot />` renders plain HTML and works inside `<flux:field>` layouts and Flux forms. No Flux component is needed for the hidden field.
