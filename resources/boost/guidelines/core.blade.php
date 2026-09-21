## darvis/livewire-honeypot

Spam protection for forms without a CAPTCHA: a hidden bait field that must stay empty and a minimum time between loading and submitting the form.

- In a Livewire component (class, single-file or multi-file), `use Darvis\LivewireHoneypot\Traits\HasHoneypot;`, put `<x-honeypot />` inside the form, call `$this->validateHoneypot()` in the submit action and `$this->resetHoneypot()` after a successful submit. With a form object, the trait stays on the component.
- In a plain form, put `<x-honeypot />` inside the form and call `app(\Darvis\LivewireHoneypot\Services\HoneypotService::class)->validate($request->all())` in the controller. Before 1.6.0 that call rejected every submission in an application that runs Laravel's `ConvertEmptyStringsToNull` middleware; upgrade instead of working around it. The component uses the Livewire variant only inside a component that uses `HasHoneypot`. Plain forms expire after `livewire-honeypot.maximum_fill_seconds` (one day); when rotating `APP_KEY`, keep the old key in `APP_PREVIOUS_KEYS`.
- Never write the bait input by hand with a name like `website`, `url` or `email`; browser autofill fills it in and blocks real visitors.
- `hp_started_at` and `hp_token` are `#[Locked]`. Never bind them with `wire:model` and never `->set()` them in tests; use `$this->travel(10)->seconds()` or set `livewire-honeypot.minimum_fill_seconds` to 0.
- `<x-honeypot />` shows the honeypot error itself, so don't add a second `@error('hp_website')`.
- When the bait is bound elsewhere with `<x-honeypot wire:model="contact.hp_website" error-key="contact.hp_website" />`, pass the same values: `$this->validateHoneypot(model: 'contact.hp_website', errorKey: 'contact.hp_website')`. Without the `model` argument the check reads the empty `hp_website` and catches nothing. The property must start as an empty string.
- Use `HasHoneypot` on the component, never on a Livewire form object: no mount hook runs there, so `validateHoneypot()` throws a `LogicException`.
- With a strict Content Security Policy, call `Vite::useCspNonce()` in the CSP middleware or pass `nonce="..."` to `<x-honeypot />`; the field is then hidden through a nonced style block.
- To log or count blocked attempts, listen for `Darvis\LivewireHoneypot\Events\SpamBlocked` (`reason`, `ip`, `component`).

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
