<?php

declare(strict_types=1);

namespace Darvis\LivewireHoneypot\Support;

/**
 * The one place that reads the package config. Callers ask this class, so a default is written
 * once and a caller cannot quietly disagree with the config file about what it is.
 *
 * `app.key` and `app.previous_keys` belong to the host application, not to this package, so
 * HoneypotService reads those where it signs and verifies a token.
 */
final class HoneypotConfig
{
    /**
     * The key a plain form renders its bait under, and the key its errors are reported under.
     */
    public static function fieldName(): string
    {
        return (string) config('livewire-honeypot.field_name', 'hp_website');
    }

    /**
     * Seconds that must pass between rendering and submitting. 0 disables the check.
     */
    public static function minimumFillSeconds(): int
    {
        return (int) config('livewire-honeypot.minimum_fill_seconds', 5);
    }

    /**
     * Seconds after which a plain form's token is refused. 0 disables the check.
     */
    public static function maximumFillSeconds(): int
    {
        return (int) config('livewire-honeypot.maximum_fill_seconds', 86400);
    }

    /**
     * Length of the random part of a generated token. Never below 1.
     */
    public static function tokenLength(): int
    {
        return max(1, (int) config('livewire-honeypot.token_length', 24));
    }
}
