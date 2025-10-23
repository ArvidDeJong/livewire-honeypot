<?php

namespace Darvis\LivewireHoneypot\Traits;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait HasHoneypot
{
    public string $hp_website = '';
    public int $hp_started_at = 0;
    public string $hp_token = '';

    protected int $minimumFillSeconds = 3;

    public function initializeHasHoneypot(): void
    {
        $this->resetHoneypot();
    }

    protected function resetHoneypot(): void
    {
        $this->hp_website = '';
        $this->hp_started_at = now()->getTimestamp();
        $this->hp_token = Str::random(24);
    }

    protected function validateHoneypot(): void
    {
        // Require presence & emptiness of the bait field, plus meta fields
        $this->validate([
            'hp_website' => 'present|size:0',
            'hp_started_at' => 'required|integer',
            'hp_token' => 'required|string|min:10',
        ], [
            'hp_website.size' => 'Spam detected.',
        ]);

        // Time-trap: minimum time spent before submit
        $elapsed = now()->getTimestamp() - (int) $this->hp_started_at;
        if ($elapsed < $this->minimumFillSeconds) {
            throw ValidationException::withMessages([
                'hp_website' => 'Form submitted too quickly.',
            ]);
        }
    }
}