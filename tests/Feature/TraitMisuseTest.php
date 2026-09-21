<?php

use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Livewire\Component;
use Livewire\Form;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleComponents\Checksum;

test('the trait on a form object fails loudly instead of reporting spam', function () {
    $component = Livewire::test(MisusedFormObjectComponent::class);

    $this->travel(10)->seconds();

    expect(fn () => $component->call('submit'))
        ->toThrow(LogicException::class, 'Use the HasHoneypot trait on the Livewire component');
});

test('a component whose snapshot predates the trait still gets a validation error, never an exception', function () {
    // A visitor has the page open while the deploy that adds the trait goes live: the snapshot has no honeypot values.
    $snapshot = Livewire::test(ComponentBeforeTheTrait::class)->snapshot;

    Livewire::component('component-with-the-trait', ComponentWithTheTrait::class);

    unset($snapshot['checksum']);
    $snapshot['memo']['name'] = 'component-with-the-trait';
    $snapshot['checksum'] = Checksum::generate($snapshot);

    $this->travel(10)->seconds();

    [$updated] = app('livewire')->update($snapshot, [], [['method' => 'submit', 'params' => [], 'path' => '', 'metadata' => []]]);

    /** @var array{memo: array{errors: array<string, array<int, string>>}} $updated */
    $updated = is_string($updated) ? json_decode($updated, true) : $updated;

    expect($updated['memo']['errors'])->toBe(['hp_website' => ['Spam detected.']]);
});

class MisusedHoneypotForm extends Form
{
    use HasHoneypot;

    public string $email = '';

    public function submit(): void
    {
        $this->validateHoneypot();
    }
}

class MisusedFormObjectComponent extends Component
{
    public MisusedHoneypotForm $form;

    public function submit(): void
    {
        $this->form->submit();
    }

    public function render(): string
    {
        return '<form wire:submit="submit"><input wire:model="form.email"></form>';
    }
}

class ComponentBeforeTheTrait extends Component
{
    public string $email = '';

    public function render(): string
    {
        return '<form wire:submit="submit"><input wire:model="email"></form>';
    }
}

class ComponentWithTheTrait extends Component
{
    use HasHoneypot;

    public string $email = '';

    public function submit(): void
    {
        $this->validateHoneypot();
    }

    public function render(): string
    {
        return '<form wire:submit="submit"><input wire:model="email"><x-honeypot /></form>';
    }
}
