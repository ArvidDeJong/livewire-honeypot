<?php

use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Darvis\LivewireHoneypot\Services\HoneypotService;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = new HoneypotService;
});

function validHoneypotData(array $overrides = [], int $secondsAgo = 10): array
{
    return array_merge([
        'hp_website' => '',
        'hp_token' => (new HoneypotService)->token(now()->subSeconds($secondsAgo)->getTimestamp()),
    ], $overrides);
}

test('it generates honeypot fields with a signed token', function () {
    $fields = $this->service->generate();

    expect($fields)->toHaveKeys(['hp_website', 'hp_started_at', 'hp_token']);
    expect($fields['hp_website'])->toBe('');
    expect($this->service->startedAtFromToken($fields['hp_token']))->toBe($fields['hp_started_at']);
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

test('it reads the bait from the generated name', function () {
    $data = validHoneypotData();
    unset($data['hp_website']);
    $data[$this->service->baitName($data['hp_token'])] = '';

    $this->service->validate($data);
})->throwsNoExceptions();

test('it fails when the generated bait field is filled', function () {
    $data = validHoneypotData();
    $data[$this->service->baitName($data['hp_token'])] = 'spam';

    $this->service->validate($data);
})->throws(ValidationException::class, 'Spam detected.');

test('it fails when submitted too quickly', function () {
    $this->service->validate(validHoneypotData(secondsAgo: 0));
})->throws(ValidationException::class, 'Form submitted too quickly.');

test('it ignores a start time sent next to the token', function () {
    $this->service->validate(validHoneypotData(['hp_started_at' => now()->subHour()->getTimestamp()], secondsAgo: 0));
})->throws(ValidationException::class, 'Form submitted too quickly.');

test('it rejects a token with a forged start time', function () {
    [$random, , $signature] = explode('.', validHoneypotData(secondsAgo: 0)['hp_token']);

    $this->service->validate(validHoneypotData([
        'hp_token' => $random.'.'.now()->subHour()->getTimestamp().'.'.$signature,
    ]));
})->throws(ValidationException::class, 'Spam detected.');

test('it rejects an unsigned token', function () {
    $this->service->validate(validHoneypotData([
        'hp_started_at' => now()->subHour()->getTimestamp(),
        'hp_token' => str_repeat('a', 24),
    ]));
})->throws(ValidationException::class, 'Spam detected.');

test('it rejects a token signed with another key', function () {
    $token = validHoneypotData()['hp_token'];
    config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32))]);

    $this->service->validate(validHoneypotData(['hp_token' => $token]));
})->throws(ValidationException::class, 'Spam detected.');

test('it fails when the token is missing', function () {
    $data = validHoneypotData();
    unset($data['hp_token']);

    $this->service->validate($data);
})->throws(ValidationException::class);

test('it respects custom minimum seconds', function () {
    $this->service->validate(validHoneypotData(secondsAgo: 2), 1);
})->throwsNoExceptions();

test('it reads a minimum fill time given as a string by env', function () {
    config(['livewire-honeypot.minimum_fill_seconds' => '0']);

    $this->service->validate(validHoneypotData(secondsAgo: 0));
})->throwsNoExceptions();

test('it uses the configured token length for the random part', function () {
    config(['livewire-honeypot.token_length' => 32]);

    expect(explode('.', $this->service->token())[0])->toHaveLength(32);
});

test('it derives a stable, inconspicuous bait name from a token', function () {
    $token = $this->service->token();
    $name = $this->service->baitName($token);

    expect($this->service->baitName($token))->toBe($name);
    expect($name)->toMatch('/^('.implode('|', HoneypotService::BAIT_WORDS).')_[0-9a-f]{4}$/');
    expect($name)->not->toMatch('/name|mail|phone|url|website|company|address/');
});

test('it uses the configured field name', function () {
    config(['livewire-honeypot.field_name' => 'company_url']);

    expect($this->service->generate())->toHaveKey('company_url')->not->toHaveKey('hp_website');

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
