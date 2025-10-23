<?php

namespace Darvis\LivewireHoneypot\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class HoneypotService
{
    public function generate(): array
    {
        return [
            'hp_website' => '',
            'hp_started_at' => now()->getTimestamp(),
            'hp_token' => Str::random(24),
        ];
    }

    public function validate(array $data, int $minimumSeconds = 3): void
    {
        validator($data, [
            'hp_website' => 'present|size:0',
            'hp_started_at' => 'required|integer',
            'hp_token' => 'required|string|min:10',
        ], [
            'hp_website.size' => 'Spam detected.',
        ])->validate();

        $elapsed = now()->getTimestamp() - (int)($data['hp_started_at'] ?? 0);
        if ($elapsed < $minimumSeconds) {
            throw ValidationException::withMessages([
                'hp_website' => 'Form submitted too quickly.',
            ]);
        }
    }
}