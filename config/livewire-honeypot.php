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
    | Token Minimum Length
    |--------------------------------------------------------------------------
    |
    | The minimum length for the honeypot token. This adds an extra
    | layer of validation to ensure the form was properly initialized.
    |
    */

    'token_min_length' => env('HONEYPOT_TOKEN_MIN_LENGTH', 10),

    /*
    |--------------------------------------------------------------------------
    | Token Length
    |--------------------------------------------------------------------------
    |
    | The length of the generated honeypot token.
    |
    */

    'token_length' => env('HONEYPOT_TOKEN_LENGTH', 24),

];
