<?php

use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Livewire\Component;
use Livewire\Livewire;

test('it initializes honeypot fields', function () {
    $component = Livewire::test(TestComponent::class);

    expect($component->hp_website)->toBe('');
    expect($component->hp_started_at)->toBeInt()->toBeGreaterThan(0);
    expect($component->hp_token)->toBeString()->toHaveLength(24);
});

test('it validates valid honeypot', function () {
    // Set minimum to 0 seconds for this test
    config(['livewire-honeypot.minimum_fill_seconds' => 0]);
    
    $component = Livewire::test(TestComponent::class);
    
    $component->call('submit');
    
    $component->assertHasNoErrors();
});

test('it fails when honeypot field is filled', function () {
    $component = Livewire::test(TestComponent::class);
    
    $component->set('hp_website', 'https://spam.com');
    $component->set('hp_started_at', now()->subSeconds(10)->getTimestamp());
    
    $component->call('submit');
    
    $component->assertHasErrors('hp_website');
});

test('it fails when submitted too quickly', function () {
    $component = Livewire::test(TestComponent::class);
    
    // Don't change hp_started_at, so it's just now
    $component->call('submit');
    
    $component->assertHasErrors('hp_website');
});

test('it resets honeypot after submission', function () {
    $component = Livewire::test(TestComponent::class);
    
    $originalToken = $component->hp_token;
    $component->set('hp_started_at', now()->subSeconds(10)->getTimestamp());
    
    config(['livewire-honeypot.minimum_fill_seconds' => 0]);
    $component->call('submit');
    
    // Token should be different after reset
    expect($component->hp_token)->not->toBe($originalToken);
    expect($component->hp_website)->toBe('');
});

test('it respects config token length', function () {
    config(['livewire-honeypot.token_length' => 32]);
    
    $component = Livewire::test(TestComponent::class);
    
    expect($component->hp_token)->toHaveLength(32);
});

// Test component for Livewire tests
class TestComponent extends Component
{
    use HasHoneypot;

    public function submit(): void
    {
        $this->validateHoneypot();
        $this->resetHoneypot();
    }

    public function render()
    {
        return '<div>Test</div>';
    }
}
