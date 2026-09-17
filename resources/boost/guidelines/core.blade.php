## darvis/livewire-honeypot

Spam protection for forms without a CAPTCHA: a hidden bait field that must stay empty and a minimum time between loading and submitting the form.

- In a Livewire component, `use Darvis\LivewireHoneypot\Traits\HasHoneypot;`, put `<x-honeypot />` inside the form, call `$this->validateHoneypot()` in the submit action and `$this->resetHoneypot()` after a successful submit.
- `hp_started_at` and `hp_token` are `#[Locked]`. Never bind them with `wire:model` and never `->set()` them in tests; use `$this->travel(10)->seconds()` to get past the minimum fill time.
- Errors land on `hp_website`. `<x-honeypot />` shows them itself, so don't add a second `@error('hp_website')`.
- For controllers, inject `Darvis\LivewireHoneypot\Services\HoneypotService`: `generate()` for the form values, `validate($request->all())` on submit.
- To log or count blocked attempts, listen for `Darvis\LivewireHoneypot\Events\SpamBlocked` (`reason`, `ip`, `component`).
- Config key: `livewire-honeypot`. Set `minimum_fill_seconds` to 0 in tests that don't care about the time trap.

@verbatim
<code-snippet name="Protect a Livewire form" lang="php">
use Darvis\LivewireHoneypot\Traits\HasHoneypot;

class ContactForm extends Component
{
    use HasHoneypot;

    public function submit(): void
    {
        $this->validate([...]);
        $this->validateHoneypot();

        // process the form ...

        $this->resetHoneypot();
    }
}
</code-snippet>
@endverbatim
