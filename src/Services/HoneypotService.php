<?php

namespace Darvis\LivewireHoneypot\Services;

use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HoneypotService
{
    /**
     * Words for generated bait names. They look like real form fields to a bot,
     * but match none of the browser autofill heuristics (name, email, url, website, company, ...).
     */
    public const BAIT_WORDS = ['reference', 'remarks', 'topic', 'occasion', 'referral', 'interest', 'preference', 'department'];

    /**
     * Honeypot values for a plain (non-Livewire) form that renders its own inputs.
     *
     * The bait field is keyed by the `field_name` config value. `hp_started_at` is kept
     * for backward compatibility only; the start time is read from the signed token.
     *
     * @return array<string, string|int>
     */
    public function generate(): array
    {
        $startedAt = now()->getTimestamp();

        return [
            $this->fieldName() => '',
            'hp_started_at' => $startedAt,
            'hp_token' => $this->token($startedAt),
        ];
    }

    /**
     * A token that carries the start time, signed with the app key: `random.timestamp.signature`.
     */
    public function token(?int $startedAt = null): string
    {
        $payload = Str::random((int) config('livewire-honeypot.token_length', 24)).'.'.($startedAt ?? now()->getTimestamp());

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * The start time inside a token, or null when the token is malformed or its signature is wrong.
     */
    public function startedAtFromToken(mixed $token): ?int
    {
        if (! is_string($token) || substr_count($token, '.') !== 2) {
            return null;
        }

        [$random, $startedAt, $signature] = explode('.', $token);

        if (! ctype_digit($startedAt) || ! hash_equals($this->sign($random.'.'.$startedAt), $signature)) {
            return null;
        }

        return (int) $startedAt;
    }

    /**
     * An inconspicuous bait field name, derived from a token so the server can find it again.
     */
    public function baitName(string $token): string
    {
        $hash = $this->sign('bait|'.$token);

        return self::BAIT_WORDS[hexdec(substr($hash, 0, 2)) % count(self::BAIT_WORDS)].'_'.substr($hash, 2, 4);
    }

    /**
     * Validate a submitted plain form, typically `$request->all()`.
     *
     * The bait is read from the generated name of `<x-honeypot />`, or from `field_name`
     * when the form renders its own inputs. Errors are reported under `field_name`.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function validate(array $data, ?int $minimumSeconds = null): void
    {
        $fieldName = $this->fieldName();
        $token = $data['hp_token'] ?? null;
        $baitName = is_string($token) ? $this->baitName($token) : null;

        $bait = match (true) {
            $baitName !== null && array_key_exists($baitName, $data) => $data[$baitName],
            array_key_exists($fieldName, $data) => $data[$fieldName],
            default => null,
        };

        $this->check(
            bait: $bait,
            startedAt: $this->startedAtFromToken($token),
            token: $token,
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

    protected function sign(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
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
