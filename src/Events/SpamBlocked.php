<?php

namespace Darvis\LivewireHoneypot\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched just before a submission is rejected by the honeypot.
 */
class SpamBlocked
{
    use Dispatchable;

    /** The bait field was filled in. */
    public const FIELD_FILLED = 'field_filled';

    /** The form was submitted before the minimum fill time passed. */
    public const SUBMITTED_TOO_QUICKLY = 'submitted_too_quickly';

    /** The start time is missing, or a plain form's token is malformed or wrongly signed. */
    public const INVALID_PAYLOAD = 'invalid_payload';

    /** A plain form was older than `maximum_fill_seconds`, for example a replayed token. */
    public const EXPIRED = 'expired';

    /**
     * @param  string  $reason  One of the constants on this class.
     * @param  string|null  $component  Class of the Livewire component, null for the service.
     */
    public function __construct(
        public readonly string $reason,
        public readonly ?string $ip,
        public readonly ?string $component = null,
    ) {}
}
