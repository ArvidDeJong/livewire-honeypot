{{--
    Markup of <x-honeypot />. The logic lives in Darvis\LivewireHoneypot\View\Components\Honeypot.

    Livewire: the bait input binds hp_website; the start time and token stay on the server.
    Plain form: the bait input plus a signed hp_token for HoneypotService::validate().
    With a CSP nonce the wrapper is hidden through a nonced style block with a stable class, so a
    Livewire update never changes the style block. Errors are shown outside the hidden wrapper.
--}}
@if ($cspNonce)
<style nonce="{{ $cspNonce }}">.{{ $hiddenClass }}{ {{ $hiddenCss }} }</style>
<div class="{{ $hiddenClass }}" aria-hidden="true">
@else
<div aria-hidden="true" style="{{ $hiddenCss }}">
@endif
    <label>
        <span>{{ __('livewire-honeypot::validation.honeypot_label') }}</span>
        <input type="text"
               name="{{ $baitName }}"
               @if ($inLivewire) wire:model="{{ $attributes->whereStartsWith('wire:model')->first() ?? 'hp_website' }}" @else value="" @endif
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
@if (isset($errors) && $errors->has($errorBagKey))
    <p class="hp-error" role="alert">{{ $errors->first($errorBagKey) }}</p>
@endif
