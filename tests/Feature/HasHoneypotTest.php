<?php

use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Darvis\LivewireHoneypot\Services\HoneypotService;
use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Illuminate\Support\Facades\Event;
use Livewire\Component;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Form;
use Livewire\Livewire;

test('it initializes honeypot fields', function () {
    $component = Livewire::test(HoneypotTestComponent::class);

    expect($component->hp_website)->toBe('');
    expect($component->hp_started_at)->toBeInt()->toBeGreaterThan(0);
    expect($component->hp_token)->toBeString()->toHaveLength(24);
});

test('it accepts a submission after the minimum fill time', function () {
    $component = Livewire::test(HoneypotTestComponent::class);

    $this->travel(10)->seconds();

    $component->call('submit')->assertHasNoErrors()->assertSet('submitted', true);
});

test('it fails when honeypot field is filled', function () {
    $component = Livewire::test(HoneypotTestComponent::class);

    $this->travel(10)->seconds();

    $component->set('hp_website', 'https://spam.com')
        ->call('submit')
        ->assertHasErrors('hp_website')
        ->assertSet('submitted', false);
});

test('it fails when submitted too quickly', function () {
    Livewire::test(HoneypotTestComponent::class)
        ->call('submit')
        ->assertHasErrors('hp_website')
        ->assertSet('submitted', false);
});

test('it does not let the client change the start time', function () {
    Livewire::test(HoneypotTestComponent::class)
        ->set('hp_started_at', now()->subMinutes(5)->getTimestamp());
})->throws(CannotUpdateLockedPropertyException::class);

test('it does not let the client change the token', function () {
    Livewire::test(HoneypotTestComponent::class)
        ->set('hp_token', str_repeat('a', 24));
})->throws(CannotUpdateLockedPropertyException::class);

test('it resets honeypot after submission', function () {
    $component = Livewire::test(HoneypotTestComponent::class);
    $originalToken = $component->hp_token;

    $this->travel(10)->seconds();
    $component->call('submit');

    expect($component->hp_token)->not->toBe($originalToken);
    expect($component->hp_website)->toBe('');
});

test('it respects config token length', function () {
    config(['livewire-honeypot.token_length' => 32]);

    expect(Livewire::test(HoneypotTestComponent::class)->hp_token)->toHaveLength(32);
});

test('it dispatches an event with the component class when spam is blocked', function () {
    Event::fake([SpamBlocked::class]);

    Livewire::test(HoneypotTestComponent::class)->call('submit');

    Event::assertDispatched(SpamBlocked::class, fn (SpamBlocked $event) => $event->reason === SpamBlocked::SUBMITTED_TOO_QUICKLY
        && $event->component === HoneypotTestComponent::class);
});

test('the blade component renders the bait field without the locked values', function () {
    $html = Livewire::test(HoneypotFormComponent::class)
        ->assertSeeHtml('wire:model="hp_website"')
        ->assertSeeHtml('autocomplete="off"')
        ->assertSeeHtml('data-1p-ignore')
        ->assertDontSeeHtml('name="hp_token"')
        ->assertDontSeeHtml('wire:model="hp_started_at"')
        ->assertDontSeeHtml('wire:model="hp_token"')
        ->html();

    expect($html)->toMatch('/name="('.implode('|', HoneypotService::BAIT_WORDS).')_[0-9a-f]{4}"/')
        ->not->toContain('name="hp_website"');
});

test('it works with a form object on the component', function () {
    $component = Livewire::test(HoneypotFormObjectComponent::class)
        ->set('form.email', 'jane@example.com')
        ->assertSeeHtml('wire:model="hp_website"');

    $this->travel(10)->seconds();

    $component->call('submit')->assertHasNoErrors()->assertSet('form.email', '');
});

test('the blade component binds a custom wire:model', function () {
    Livewire::test(HoneypotFormComponent::class, ['model' => 'form.hp_website'])
        ->assertSeeHtml('wire:model="form.hp_website"');
});

test('the blade component shows the error outside the hidden field', function () {
    Livewire::test(HoneypotFormComponent::class)
        ->call('submit')
        ->assertSeeHtml('<p class="hp-error" role="alert">Form submitted too quickly.</p>');
});

class HoneypotTestComponent extends Component
{
    use HasHoneypot;

    public bool $submitted = false;

    public function submit(): void
    {
        $this->validateHoneypot();
        $this->submitted = true;
        $this->resetHoneypot();
    }

    public function render(): string
    {
        return '<div>Test</div>';
    }
}

class HoneypotFormComponent extends Component
{
    use HasHoneypot;

    public ?string $model = null;

    public function submit(): void
    {
        $this->validateHoneypot();
    }

    public function render(): string
    {
        return $this->model
            ? '<form><x-honeypot wire:model="{{ $model }}" /></form>'
            : '<form><x-honeypot /></form>';
    }
}

class HoneypotContactForm extends Form
{
    public string $email = '';
}

class HoneypotFormObjectComponent extends Component
{
    use HasHoneypot;

    public HoneypotContactForm $form;

    public function submit(): void
    {
        $this->form->validate(['email' => 'required|email']);
        $this->validateHoneypot();

        $this->form->reset();
        $this->resetHoneypot();
    }

    public function render(): string
    {
        return '<form wire:submit="submit"><input wire:model="form.email"><x-honeypot /></form>';
    }
}
