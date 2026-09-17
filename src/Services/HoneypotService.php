<?php

namespace Darvis\LivewireHoneypot\Services;

use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HoneypotService
{
    /**
     * Fresh honeypot values for a plain (non-Livewire) form.
     *
     * The bait field is keyed by the `field_name` config value.
     *
     * @return array<string, string|int>
     */
    public function generate(): array
    {
        return [
            $this->fieldName() => '',
            'hp_started_at' => now()->getTimestamp(),
            'hp_token' => Str::random((int) config('livewire-honeypot.token_length', 24)),
        ];
    }

    /**
     * Validate submitted honeypot values, typically `$request->all()`.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function validate(array $data, ?int $minimumSeconds = null): void
    {
        $fieldName = $this->fieldName();

        $this->check(
            bait: array_key_exists($fieldName, $data) ? $data[$fieldName] : null,
            startedAt: $data['hp_started_at'] ?? null,
            token: $data['hp_token'] ?? null,
            errorKey: $fieldName,
            minimumSeconds: $minimumSeconds,
        );
    }

    /**
     * Run the honeypot checks and throw a validation error under `$errorKey`.
     *
     * A null `$bait` means the field was not submitted at all.
     *
     * @throws ValidationException
     */
    public function check(
        mixed $bait,
        mixed $startedAt,
        mixed $token,
        string $errorKey,
        ?int $minimumSeconds = null,
        ?string $component = null,
    ): void {
        if ($bait === null || ! is_scalar($bait) || (string) $bait !== '') {
            $this->reject(SpamBlocked::FIELD_FILLED, 'spam_detected', $errorKey, $component);
        }

        $tokenMinLength = (int) config('livewire-honeypot.token_min_length', 10);

        if (! is_numeric($startedAt) || (int) $startedAt <= 0
            || ! is_string($token) || strlen($token) < $tokenMinLength) {
            $this->reject(SpamBlocked::INVALID_PAYLOAD, 'spam_detected', $errorKey, $component);
        }

        $minimumSeconds ??= (int) config('livewire-honeypot.minimum_fill_seconds', 5);

        if (now()->getTimestamp() - (int) $startedAt < $minimumSeconds) {
            $this->reject(SpamBlocked::SUBMITTED_TOO_QUICKLY, 'submitted_too_quickly', $errorKey, $component);
        }
    }

    protected function fieldName(): string
    {
        return (string) config('livewire-honeypot.field_name', 'hp_website');
    }

    /**
     * @throws ValidationException
     */
    protected function reject(string $reason, string $message, string $errorKey, ?string $component): never
    {
        SpamBlocked::dispatch($reason, request()->ip(), $component);

        throw ValidationException::withMessages([
            $errorKey => __('livewire-honeypot::validation.'.$message),
        ]);
    }
}
