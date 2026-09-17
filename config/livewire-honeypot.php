<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Minimum Fill Time (seconds)
    |--------------------------------------------------------------------------
    |
    | The minimum time in seconds that must pass between form load and
    | submission. This helps prevent automated bot submissions.
    | Set to 0 to disable the time check.
    |
    */

    'minimum_fill_seconds' => env('HONEYPOT_MINIMUM_FILL_SECONDS', 5),

    /*
    |--------------------------------------------------------------------------
    | Maximum Fill Time (seconds)
    |--------------------------------------------------------------------------
    |
    | Plain forms are rejected when they were loaded longer ago than this,
    | so a token scraped from the page can't be replayed forever. Livewire
    | forms keep their start time on the server and never expire.
    | Default: one day. Set to 0 to disable.
    |
    */

    'maximum_fill_seconds' => env('HONEYPOT_MAXIMUM_FILL_SECONDS', 86400),

    /*
    |--------------------------------------------------------------------------
    | Honeypot Field Name
    |--------------------------------------------------------------------------
    |
    | The key HoneypotService::validate() reads the bait from when a plain
    | form renders its own inputs, and the key its errors are reported under.
    | <x-honeypot /> renders a generated name instead, which browser autofill
    | leaves alone. Avoid names like "website" or "email" in your own forms.
    |
    */

    'field_name' => env('HONEYPOT_FIELD_NAME', 'hp_website'),

    /*
    |--------------------------------------------------------------------------
    | Token Length
    |--------------------------------------------------------------------------
    |
    | The length of the random part of a generated token.
    |
    */

    'token_length' => env('HONEYPOT_TOKEN_LENGTH', 24),

];
