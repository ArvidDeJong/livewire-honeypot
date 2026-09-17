<?php

namespace Darvis\LivewireHoneypot\Traits;

use Darvis\LivewireHoneypot\Services\HoneypotService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

trait HasHoneypot
{
    public string $hp_website = '';

    /**
     * Unix time the form was loaded. Locked, so a client cannot move it back.
     */
    #[Locked]
    public int $hp_started_at = 0;

    /**
     * Random token. Locked, so a client cannot swap it.
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
        $this->hp_token = Str::random((int) config('livewire-honeypot.token_length', 24));
    }

    /**
     * @throws ValidationException
     */
    protected function validateHoneypot(): void
    {
        app(HoneypotService::class)->check(
            bait: $this->hp_website,
            startedAt: $this->hp_started_at,
            token: $this->hp_token,
            errorKey: 'hp_website',
            component: static::class,
        );
    }
}
