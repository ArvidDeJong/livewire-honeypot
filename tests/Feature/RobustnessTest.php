<?php

use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Darvis\LivewireHoneypot\Services\HoneypotService;
use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Livewire;

function plainSubmission(HoneypotService $service, ?int $startedAt = null): array
{
    $token = $service->token($startedAt ?? now()->subSeconds(10)->getTimestamp());

    return ['hp_token' => $token, $service->baitName($token) => ''];
}

test('a plain form inside a Livewire component without the trait gets a signed token', function () {
    $html = Livewire::test(NewsletterFooterComponent::class)->html();

    preg_match('/name="hp_token" value="([^"]+)"/', $html, $token);

    expect($token[1] ?? null)->not->toBeNull();
    expect(app(HoneypotService::class)->startedAtFromToken($token[1]))->toBe(now()->getTimestamp());
    expect($html)->not->toContain('wire:model="hp_website"');
});

test('a component with the trait still renders the Livewire variant', function () {
    Livewire::test(TraitFormComponent::class)
        ->assertSeeHtml('wire:model="hp_website"')
        ->assertDontSeeHtml('name="hp_token"');
});

test('the error-key attribute changes where the error is read', function () {
    Livewire::test(TraitFormComponent::class, ['errorKey' => 'contact.hp_website'])
        ->call('submitWithCustomKey')
        ->assertSeeHtml('<p class="hp-error" role="alert">Form submitted too quickly.</p>');
});

test('signing without an app key fails loudly instead of accepting forged tokens', function () {
    config(['app.key' => '']);

    app(HoneypotService::class)->token();
})->throws(MissingAppKeyException::class);

test('a form signed with a previous app key still validates after rotation', function () {
    $service = app(HoneypotService::class);
    $oldKey = config('app.key');
    $data = plainSubmission($service);

    config(['app.key' => 'base64:'.base64_encode(str_repeat('n', 32)), 'app.previous_keys' => [$oldKey]]);

    $service->validate($data);
})->throwsNoExceptions();

test('a filled bait is still caught on a form signed with a previous key', function () {
    $service = app(HoneypotService::class);
    $oldKey = config('app.key');
    $token = $service->token(now()->subSeconds(10)->getTimestamp());
    $data = ['hp_token' => $token, $service->baitName($token) => 'spam'];

    config(['app.key' => 'base64:'.base64_encode(str_repeat('n', 32)), 'app.previous_keys' => [$oldKey]]);

    $service->validate($data);
})->throws(ValidationException::class, 'Spam detected.');

test('a form signed with a key that is no longer listed is rejected', function () {
    $service = app(HoneypotService::class);
    $data = plainSubmission($service);

    config(['app.key' => 'base64:'.base64_encode(str_repeat('n', 32)), 'app.previous_keys' => []]);

    $service->validate($data);
})->throws(ValidationException::class, 'Spam detected.');

test('a plain form older than the maximum fill time is rejected as expired', function () {
    Event::fake([SpamBlocked::class]);
    $service = app(HoneypotService::class);
    $data = plainSubmission($service);

    $this->travel(2)->days();

    try {
        $service->validate($data);
        $this->fail('Expected the form to be expired.');
    } catch (ValidationException $e) {
        expect($e->errors())->toBe(['hp_website' => ['This form has expired. Please try again.']]);
    }

    Event::assertDispatched(SpamBlocked::class, fn (SpamBlocked $event) => $event->reason === SpamBlocked::EXPIRED);
});

test('a plain form just inside the maximum fill time is accepted', function () {
    $service = app(HoneypotService::class);
    $data = plainSubmission($service, now()->getTimestamp());

    $this->travel(86400)->seconds();

    $service->validate($data);
})->throwsNoExceptions();

test('setting the maximum fill time to 0 disables expiry', function () {
    config(['livewire-honeypot.maximum_fill_seconds' => '0']);
    $service = app(HoneypotService::class);
    $data = plainSubmission($service);

    $this->travel(30)->days();

    $service->validate($data);
})->throwsNoExceptions();

test('spam is reported before expiry', function () {
    $service = app(HoneypotService::class);
    $token = $service->token(now()->getTimestamp());

    $this->travel(2)->days();

    $service->validate(['hp_token' => $token, $service->baitName($token) => 'spam']);
})->throws(ValidationException::class, 'Spam detected.');

test('the removed token_min_length setting no longer affects validation', function () {
    config(['livewire-honeypot.token_min_length' => 500]);

    app(HoneypotService::class)->validate(plainSubmission(app(HoneypotService::class)));
})->throwsNoExceptions();

class NewsletterFooterComponent extends Component
{
    public function render(): string
    {
        return '<div><form method="POST" action="/newsletter"><x-honeypot /></form></div>';
    }
}

class TraitFormComponent extends Component
{
    use HasHoneypot;

    public ?string $errorKey = null;

    public function submitWithCustomKey(): void
    {
        try {
            $this->validateHoneypot();
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['contact.hp_website' => $e->errors()['hp_website']]);
        }
    }

    public function render(): string
    {
        return $this->errorKey
            ? '<form><x-honeypot error-key="contact.hp_website" /></form>'
            : '<form><x-honeypot /></form>';
    }
}
