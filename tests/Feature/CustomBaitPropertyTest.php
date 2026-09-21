<?php

use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Illuminate\Support\Facades\Event;
use Livewire\Component;
use Livewire\Livewire;

test('a bait bound to another property is checked when its path is passed', function () {
    $component = Livewire::test(CustomBaitComponent::class)->set('contact.hp_website', 'spam');

    $this->travel(10)->seconds();

    $component->call('submit')
        ->assertHasErrors('contact.hp_website')
        ->assertHasNoErrors('hp_website')
        ->assertSet('submitted', false)
        ->assertSeeHtml('<p class="hp-error" role="alert">Spam detected.</p>');
});

test('an empty bait on another property passes', function () {
    $component = Livewire::test(CustomBaitComponent::class);

    $this->travel(10)->seconds();

    $component->call('submit')->assertHasNoErrors()->assertSet('submitted', true);
});

test('the time trap reports under the passed error key and names the component', function () {
    Event::fake([SpamBlocked::class]);

    Livewire::test(CustomBaitComponent::class)
        ->call('submit')
        ->assertHasErrors('contact.hp_website')
        ->assertSeeHtml('<p class="hp-error" role="alert">Form submitted too quickly.</p>');

    Event::assertDispatched(SpamBlocked::class, fn (SpamBlocked $event) => $event->reason === SpamBlocked::SUBMITTED_TOO_QUICKLY
        && $event->component === CustomBaitComponent::class);
});

test('the error key can be changed on its own', function () {
    Livewire::test(CustomErrorKeyComponent::class)
        ->set('hp_website', 'spam')
        ->call('submit')
        ->assertHasErrors('form.bait')
        ->assertHasNoErrors('hp_website');
});

test('without arguments the bait is hp_website and the error key is hp_website', function () {
    $component = Livewire::test(CustomErrorKeyComponent::class)->set('hp_website', 'spam');

    $this->travel(10)->seconds();

    $component->call('submitDefault')->assertHasErrors('hp_website');
});

test('a component that defines its own validateHoneypot() without arguments keeps working', function () {
    $component = Livewire::test(OwnValidateHoneypotComponent::class);

    $this->travel(10)->seconds();

    $component->call('submit')->assertHasNoErrors()->assertSet('calls', 1);
});

class CustomBaitComponent extends Component
{
    use HasHoneypot;

    /** @var array<string, string> */
    public array $contact = ['hp_website' => ''];

    public bool $submitted = false;

    public function submit(): void
    {
        $this->validateHoneypot(model: 'contact.hp_website', errorKey: 'contact.hp_website');

        $this->submitted = true;
    }

    public function render(): string
    {
        return '<form wire:submit="submit"><x-honeypot wire:model="contact.hp_website" error-key="contact.hp_website" /></form>';
    }
}

class CustomErrorKeyComponent extends Component
{
    use HasHoneypot;

    public function submit(): void
    {
        $this->validateHoneypot(errorKey: 'form.bait');
    }

    public function submitDefault(): void
    {
        $this->validateHoneypot();
    }

    public function render(): string
    {
        return '<form wire:submit="submit"><x-honeypot error-key="form.bait" /></form>';
    }
}

class OwnValidateHoneypotComponent extends Component
{
    use HasHoneypot;

    public int $calls = 0;

    public function submit(): void
    {
        $this->validateHoneypot();
    }

    protected function validateHoneypot(): void
    {
        $this->calls++;
    }

    public function render(): string
    {
        return '<form wire:submit="submit"><x-honeypot /></form>';
    }
}
