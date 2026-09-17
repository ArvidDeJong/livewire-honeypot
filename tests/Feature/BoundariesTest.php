<?php

use Darvis\LivewireHoneypot\HoneypotServiceProvider;
use Darvis\LivewireHoneypot\Services\HoneypotService;
use Darvis\LivewireHoneypot\Traits\HasHoneypot;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Livewire;

/**
 * Exact boundaries, config defaults and derived values. Without these, a changed
 * comparison, default or offset passes the rest of the suite unnoticed.
 */
function submissionStartedAt(int $secondsAgo): array
{
    $service = app(HoneypotService::class);
    $token = $service->token(now()->subSeconds($secondsAgo)->getTimestamp());

    return ['hp_token' => $token, $service->baitName($token) => ''];
}

test('a submission at exactly the minimum fill time is accepted', function () {
    $this->freezeTime();

    app(HoneypotService::class)->validate(submissionStartedAt(5));
})->throwsNoExceptions();

test('a submission one second before the minimum fill time is rejected', function () {
    $this->freezeTime();

    app(HoneypotService::class)->validate(submissionStartedAt(4));
})->throws(ValidationException::class, 'Form submitted too quickly.');

test('a form one second past the maximum fill time is rejected', function () {
    $this->freezeTime();

    app(HoneypotService::class)->validate(submissionStartedAt(86401));
})->throws(ValidationException::class, 'This form has expired. Please try again.');

test('a signed token with a start time of zero is not trusted', function () {
    $service = app(HoneypotService::class);
    $token = $service->token(0);

    $service->validate(['hp_token' => $token, $service->baitName($token) => '']);
})->throws(ValidationException::class, 'Spam detected.');

test('the defaults apply when the config is empty', function () {
    config(['livewire-honeypot' => []]);
    $service = app(HoneypotService::class);

    expect($service->minimumFillSeconds())->toBe(5);
    expect($service->maximumFillSeconds())->toBe(86400);
    expect($service->fieldName())->toBe('hp_website');
    expect(explode('.', $service->token())[0])->toHaveLength(24);
});

test('settings given as strings by env are used as numbers', function () {
    config(['livewire-honeypot.maximum_fill_seconds' => '60', 'livewire-honeypot.token_length' => '8']);
    $service = app(HoneypotService::class);

    expect(explode('.', $service->token())[0])->toHaveLength(8);

    $service->validate(submissionStartedAt(61));
})->throws(ValidationException::class, 'This form has expired. Please try again.');

test('the bait name and wrapper class are derived exactly as before', function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('g', 32))]);
    $service = app(HoneypotService::class);

    expect($service->baitName('FIXEDTOKENVALUE.1700000000.sig'))->toBe('interest_6f2c');
    expect($service->wrapperClass())->toBe('f1326243e');
});

test('a plain form reports its error under the configured field name', function () {
    config(['livewire-honeypot.field_name' => 'company_url']);
    view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag(['company_url' => 'Spam detected.'])));

    expect(Blade::render('<x-honeypot />'))->toContain('<p class="hp-error" role="alert">Spam detected.</p>');
});

test('a Livewire form keeps hp_website as its error key when field_name differs', function () {
    config(['livewire-honeypot.field_name' => 'company_url']);

    Livewire::test(BoundaryFormComponent::class)
        ->call('submit')
        ->assertSeeHtml('<p class="hp-error" role="alert">Form submitted too quickly.</p>');
});

test('every publish tag points at the right file', function () {
    $provider = new HoneypotServiceProvider(app());
    $provider->boot();

    foreach ([
        'livewire-honeypot-config' => [__DIR__.'/../../config/livewire-honeypot.php' => config_path('livewire-honeypot.php')],
        'livewire-honeypot-translations' => [__DIR__.'/../../resources/lang' => lang_path('vendor/livewire-honeypot')],
        'livewire-honeypot-views' => [__DIR__.'/../../resources/views' => resource_path('views/vendor/livewire-honeypot')],
    ] as $tag => $expected) {
        $published = ServiceProvider::pathsToPublish(HoneypotServiceProvider::class, $tag);

        expect(array_map('realpath', array_keys($published)))->toBe(array_map('realpath', array_keys($expected)), $tag);
        expect(array_values($published))->toBe(array_values($expected), $tag);
    }
});

test('the package config is merged, so a host app needs no published config', function () {
    expect(config('livewire-honeypot'))->toMatchArray([
        'minimum_fill_seconds' => 5,
        'maximum_fill_seconds' => 86400,
        'field_name' => 'hp_website',
        'token_length' => 24,
    ]);
});

test('the component is registered, also with an empty view cache', function () {
    Artisan::call('view:clear');

    expect(Blade::render('<x-honeypot />'))->toContain('name="hp_token"');
});

class BoundaryFormComponent extends Component
{
    use HasHoneypot;

    public function submit(): void
    {
        $this->validateHoneypot();
    }

    public function render(): string
    {
        return '<form><x-honeypot /></form>';
    }
}
