{{--
    Honeypot component. Usage: <x-honeypot /> inside a Livewire form.

    The bait input is bound to hp_website; pass wire:model="form.hp_website" to bind elsewhere.
    The start time and token stay on the server (locked properties), so they are not rendered.
    Errors are shown outside the hidden wrapper, otherwise a real visitor would never see them.
--}}
@php
    $model = $attributes->whereStartsWith('wire:model')->first() ?? 'hp_website';
    $errorKey = $attributes->get('error-key', 'hp_website');
@endphp
<div class="hp-field" aria-hidden="true" style="position:absolute!important;left:-10000px!important;top:auto!important;width:1px!important;height:1px!important;overflow:hidden!important;">
    <label>
        <span>{{ __('livewire-honeypot::validation.honeypot_label') }}</span>
        <input type="text"
               name="{{ config('livewire-honeypot.field_name', 'hp_website') }}"
               wire:model="{{ $model }}"
               tabindex="-1"
               autocomplete="off" />
    </label>
</div>
@error($errorKey)
    <p class="hp-error" role="alert">{{ $message }}</p>
@enderror
