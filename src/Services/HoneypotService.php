<?php

namespace Darvis\LivewireHoneypot\Services;

use Darvis\LivewireHoneypot\Events\SpamBlocked;
use Darvis\LivewireHoneypot\Support\HoneypotConfig;
use Illuminate\Encryption\MissingAppKeyException;
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
     *
     * @throws MissingAppKeyException
     */
    public function token(?int $startedAt = null): string
    {
        $payload = Str::random($this->tokenLength()).'.'.($startedAt ?? now()->getTimestamp());

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * The start time inside a token, or null when the token is malformed or its signature
     * matches neither the app key nor one of the previous keys.
     *
     * @throws MissingAppKeyException
     */
    public function startedAtFromToken(mixed $token): ?int
    {
        return $this->verify($token)['startedAt'] ?? null;
    }

    /**
     * An inconspicuous bait field name, derived from a token so the server can find it again.
     *
     * @throws MissingAppKeyException
     */
    public function baitName(string $token, ?string $key = null): string
    {
        $hash = $this->sign('bait|'.$token, $key);

        return self::BAIT_WORDS[hexdec(substr($hash, 0, 2)) % count(self::BAIT_WORDS)].'_'.substr($hash, 2, 4);
    }

    /**
     * A class name for the hidden wrapper that is stable per app but not recognisable as a honeypot.
     *
     * @throws MissingAppKeyException
     */
    public function wrapperClass(): string
    {
        return 'f'.substr($this->sign('wrapper'), 0, 8);
    }

    /**
     * Validate a submitted plain form, typically `$request->all()`.
     *
     * The bait is read from the generated name of `<x-honeypot />`, or from `field_name`
     * when the form renders its own inputs. Errors are reported under `field_name`.
     * A form older than `maximum_fill_seconds` is rejected, so a scraped token can't be replayed forever.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws MissingAppKeyException
     */
    public function validate(array $data, ?int $minimumSeconds = null): void
    {
        $fieldName = $this->fieldName();
        $token = $data['hp_token'] ?? null;
        $verified = $this->verify($token);
        $baitName = $verified !== null && is_string($token) ? $this->baitName($token, $verified['key']) : null;

        $bait = match (true) {
            $baitName !== null && array_key_exists($baitName, $data) => $data[$baitName],
            array_key_exists($fieldName, $data) => $data[$fieldName],
            default => null,
        };

        $this->check(
            bait: $bait,
            startedAt: $verified['startedAt'] ?? null,
            token: $token,
            errorKey: $fieldName,
            minimumSeconds: $minimumSeconds,
        );

        $maximumSeconds = $this->maximumFillSeconds();

        if ($maximumSeconds > 0 && now()->getTimestamp() - ($verified['startedAt'] ?? 0) > $maximumSeconds) {
            $this->reject(SpamBlocked::EXPIRED, 'form_expired', $fieldName, null);
        }
    }

    /**
     * Run the bait, start time and time trap checks and throw a validation error under `$errorKey`.
     *
     * A null `$bait` means the field was not submitted at all.
     *
     * @param  mixed  $token  No longer checked since 1.4.0: Livewire keeps it locked and plain forms verify its signature. Kept for compatibility.
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

        if (! is_numeric($startedAt) || (int) $startedAt <= 0) {
            $this->reject(SpamBlocked::INVALID_PAYLOAD, 'spam_detected', $errorKey, $component);
        }

        if (now()->getTimestamp() - (int) $startedAt < ($minimumSeconds ?? $this->minimumFillSeconds())) {
            $this->reject(SpamBlocked::SUBMITTED_TOO_QUICKLY, 'submitted_too_quickly', $errorKey, $component);
        }
    }

    public function fieldName(): string
    {
        return HoneypotConfig::fieldName();
    }

    public function minimumFillSeconds(): int
    {
        return HoneypotConfig::minimumFillSeconds();
    }

    public function maximumFillSeconds(): int
    {
        return HoneypotConfig::maximumFillSeconds();
    }

    protected function tokenLength(): int
    {
        return HoneypotConfig::tokenLength();
    }

    /**
     * The start time and the key that signed a valid token.
     *
     * @return array{startedAt: int, key: string}|null
     *
     * @throws MissingAppKeyException
     */
    protected function verify(mixed $token): ?array
    {
        if (! is_string($token) || substr_count($token, '.') !== 2) {
            return null;
        }

        [$random, $startedAt, $signature] = explode('.', $token);

        if (! ctype_digit($startedAt)) {
            return null;
        }

        foreach ($this->keys() as $key) {
            if (hash_equals($this->sign($random.'.'.$startedAt, $key), $signature)) {
                return ['startedAt' => (int) $startedAt, 'key' => $key];
            }
        }

        return null;
    }

    /**
     * The app key first, then the previous keys, so forms stay valid while `APP_KEY` is rotated.
     *
     * @return array<int, string>
     *
     * @throws MissingAppKeyException
     */
    protected function keys(): array
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new MissingAppKeyException;
        }

        $previous = array_filter((array) config('app.previous_keys', []), fn ($value) => is_string($value) && $value !== '');

        return array_values(array_unique([$key, ...$previous]));
    }

    /**
     * @throws MissingAppKeyException
     */
    protected function sign(string $value, ?string $key = null): string
    {
        return hash_hmac('sha256', $value, $key ?? $this->keys()[0]);
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
