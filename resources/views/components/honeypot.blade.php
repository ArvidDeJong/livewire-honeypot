{{--
    Honeypot component. Place <x-honeypot /> inside a Livewire form or a plain form.

    Livewire: the bait input binds hp_website (pass wire:model="..." to bind elsewhere). The start
    time and token stay on the server as locked properties, so they are not rendered.
    Plain form: renders the bait input and a signed hp_token for HoneypotService::validate().

    The bait gets a generated name that browser autofill and password managers leave alone,
    and is hidden like a screen-reader-only element instead of being pushed off screen.
    Errors are shown outside the hidden wrapper, otherwise a real visitor would never see them.
--}}
@php
    $honeypot = app(\Darvis\LivewireHoneypot\Services\HoneypotService::class);
    $inLivewire = isset($__livewire);

    if ($inLivewire) {
        $model = $attributes->whereStartsWith('wire:model')->first() ?? 'hp_website';
        $baitName = $honeypot->baitName(\Illuminate\Support\Str::random(16));
    } else {
        $token = $honeypot->token();
        $baitName = $honeypot->baitName($token);
    }

    $errorKey = $attributes->get('error-key', $inLivewire ? 'hp_website' : config('livewire-honeypot.field_name', 'hp_website'));
@endphp
<div aria-hidden="true" style="position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip-path:inset(50%)!important;white-space:nowrap!important;border:0!important;">
    <label>
        <span>{{ __('livewire-honeypot::validation.honeypot_label') }}</span>
        <input type="text"
               name="{{ $baitName }}"
               @if ($inLivewire) wire:model="{{ $model }}" @else value="" @endif
               tabindex="-1"
               autocomplete="off"
               data-1p-ignore
               data-lpignore="true"
               data-bwignore
               data-form-type="other" />
    </label>
</div>
@unless ($inLivewire)
    <input type="hidden" name="hp_token" value="{{ $token }}">
@endunless
{{-- $errors is missing when a view renders without the web middleware. --}}
@if (isset($errors) && $errors->has($errorKey))
    <p class="hp-error" role="alert">{{ $errors->first($errorKey) }}</p>
@endif
