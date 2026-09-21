<?php

namespace Darvis\LivewireHoneypot\Traits;

use Darvis\LivewireHoneypot\Services\HoneypotService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use LogicException;

trait HasHoneypot
{
    public string $hp_website = '';

    /**
     * Unix time the form was loaded. Locked, so a client cannot move it back.
     */
    #[Locked]
    public int $hp_started_at = 0;

    /**
     * Token for this form, kept for compatibility. Locked, so a client cannot swap it.
     */
    #[Locked]
    public string $hp_token = '';

    public function mountHasHoneypot(): void
    {
        $this->resetHoneypot();
    }

    protected function resetHoneypot(): void
    {
        $this->hp_website = '';
        $this->hp_started_at = now()->getTimestamp();
        $this->hp_token = app(HoneypotService::class)->token($this->hp_started_at);
    }

    /**
     * Check the honeypot and throw a validation error when the submission looks like spam.
     *
     * Both arguments mirror the attributes of `<x-honeypot />`: pass `$model` when the component binds
     * the bait with `wire:model="..."` to another property, and `$errorKey` when it sets `error-key="..."`.
     *
     * @param  string|null  $model  Dot path of the property that holds the bait. Default `hp_website`.
     * @param  string|null  $errorKey  Key the error is reported under. Default `hp_website`.
     *
     * @throws ValidationException
     * @throws LogicException
     */
    protected function validateHoneypot(?string $model = null, ?string $errorKey = null): void
    {
        /*
         * Only a Livewire component runs mountHasHoneypot(). On anything else, such as a form object,
         * the start time is never set and every visitor would be reported as spam. That is a mistake
         * in the code, not spam, so it fails loudly. A component is left alone on purpose: a tab that
         * was opened before the trait was added still carries a snapshot without a start time.
         */
        if ($this->hp_started_at === 0 && ! $this instanceof Component) {
            throw new LogicException(
                'The honeypot of '.static::class.' has no start time, because Livewire only runs mountHasHoneypot() on a component. '
                .'Use the HasHoneypot trait on the Livewire component, not on a form object, and call $this->validateHoneypot() there.'
            );
        }

        app(HoneypotService::class)->check(
            bait: $model === null ? $this->hp_website : data_get($this, $model),
            startedAt: $this->hp_started_at,
            token: $this->hp_token,
            errorKey: $errorKey ?? 'hp_website',
            component: static::class,
        );
    }
}
