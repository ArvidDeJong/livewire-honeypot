<?php

use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Darvis\LivewireHoneypot\Services\HoneypotService;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = new HoneypotService;
});

function validHoneypotData(array $overrides = []): array
{
    return array_merge([
        'hp_website' => '',
        'hp_started_at' => now()->subSeconds(10)->getTimestamp(),
        'hp_token' => str_repeat('a', 24),
    ], $overrides);
}

test('it generates honeypot fields', function () {
    $fields = $this->service->generate();

    expect($fields)->toHaveKeys(['hp_website', 'hp_started_at', 'hp_token']);
    expect($fields['hp_website'])->toBe('');
    expect($fields['hp_started_at'])->toBeInt();
    expect($fields['hp_token'])->toBeString()->toHaveLength(24);
});

test('it validates valid honeypot data', function () {
    $this->service->validate(validHoneypotData());
})->throwsNoExceptions();

test('it fails when honeypot field is filled', function () {
    $this->service->validate(validHoneypotData(['hp_website' => 'https://spam.com']));
})->throws(ValidationException::class, 'Spam detected.');

test('it fails when honeypot field is missing', function () {
    $data = validHoneypotData();
    unset($data['hp_website']);

    $this->service->validate($data);
})->throws(ValidationException::class, 'Spam detected.');

test('it fails when submitted too quickly', function () {
    $this->service->validate(validHoneypotData(['hp_started_at' => now()->getTimestamp()]));
})->throws(ValidationException::class, 'Form submitted too quickly.');

test('it fails when token is too short', function () {
    $this->service->validate(validHoneypotData(['hp_token' => 'short']));
})->throws(ValidationException::class);

test('it fails when the start time is missing', function () {
    $data = validHoneypotData();
    unset($data['hp_started_at']);

    $this->service->validate($data);
})->throws(ValidationException::class);

test('it respects custom minimum seconds', function () {
    $this->service->validate(validHoneypotData(['hp_started_at' => now()->subSeconds(2)->getTimestamp()]), 1);
})->throwsNoExceptions();

test('it reads a minimum fill time given as a string by env', function () {
    config(['livewire-honeypot.minimum_fill_seconds' => '0']);

    $this->service->validate(validHoneypotData(['hp_started_at' => now()->getTimestamp()]));
})->throwsNoExceptions();

test('it uses config values', function () {
    config(['livewire-honeypot.token_length' => 32]);

    expect($this->service->generate()['hp_token'])->toHaveLength(32);
});

test('it uses the configured field name', function () {
    config(['livewire-honeypot.field_name' => 'company_url']);

    $fields = $this->service->generate();
    expect($fields)->toHaveKey('company_url')->not->toHaveKey('hp_website');

    try {
        $this->service->validate(validHoneypotData(['company_url' => 'spam']));
        $this->fail('Expected a validation exception.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('company_url');
    }
});

test('it translates error messages', function () {
    app()->setLocale('nl');

    $this->service->validate(validHoneypotData(['hp_website' => 'spam']));
})->throws(ValidationException::class, 'Spam gedetecteerd.');

test('it dispatches an event when spam is blocked', function () {
    Event::fake([SpamBlocked::class]);

    try {
        $this->service->validate(validHoneypotData(['hp_website' => 'spam']));
    } catch (ValidationException) {
    }

    Event::assertDispatched(SpamBlocked::class, fn (SpamBlocked $event) => $event->reason === SpamBlocked::FIELD_FILLED
        && $event->component === null);
});

test('it does not dispatch an event for a valid submission', function () {
    Event::fake([SpamBlocked::class]);

    $this->service->validate(validHoneypotData());

    Event::assertNotDispatched(SpamBlocked::class);
});
