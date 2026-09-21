---
title: "How it works"
nav_order: 7
description: "The four checks darvis/livewire-honeypot runs on a submission, the message each one shows, how the bait field is hidden, and what a honeypot does not stop."
---

# How it works

A honeypot is a form field that visitors never see and bots fill in. A time trap refuses a form that comes back faster than a person can type. This package combines both.

## Which checks run, and in which order

A submission passes these checks, in this order. The first one that fails stops the submission with a validation error and a [`SpamBlocked` event](configuration.md#logging-blocked-submissions).

| Check | Fails when | Message the visitor sees | Event reason |
| --- | --- | --- | --- |
| Bait field | the hidden field was filled in, or was not submitted at all | Spam detected. | `FIELD_FILLED` |
| Payload | the start time is missing, or a plain form's token is missing, malformed or wrongly signed | Spam detected. | `INVALID_PAYLOAD` |
| Time trap | fewer than `minimum_fill_seconds` (default 5) passed since the form was loaded | Form submitted too quickly. | `SUBMITTED_TOO_QUICKLY` |
| Expiry (plain forms only) | more than `maximum_fill_seconds` passed, one day by default | This form has expired. Please try again. | `EXPIRED` |

A submission at exactly `minimum_fill_seconds` passes. The messages are translated; [Configuration](configuration.md#changing-the-messages) lists the keys.

## The bait field

Bots that fill in every input also fill in the bait. Visitors never see it:

- It is hidden like a screen-reader-only element (1×1 px, clipped, through a nonced style block when the site has a [Content Security Policy](configuration.md#content-security-policy)) and marked `aria-hidden` with `tabindex="-1"`, so keyboard and screen reader users skip it too. It is not moved off screen with something like `left:-10000px`, a pattern a bot can look for.
- Its name is generated, for example `referral_3f9a`. A fixed name like `hp_website` is simple for a bot to skip, and a name with "website", "email" or "company" in it gets filled in by browser autofill. That would block a real visitor.
- It carries `autocomplete="off"` and the attributes that ask password managers to skip a field: `data-1p-ignore` (1Password), `data-lpignore="true"` (LastPass), `data-bwignore` (Bitwarden) and `data-form-type="other"` (Dashlane).

## The start time

Where the start time lives decides whether a bot can fake it.

- **Livewire:** `hp_started_at` and `hp_token` are `#[Locked]` properties. They live in the component snapshot that Livewire checksums, and a request that changes them is rejected.
- **Plain forms:** the time is inside `hp_token` as `random.timestamp.signature`, signed with HMAC-SHA256 and your `APP_KEY`. A changed timestamp breaks the signature. Without an `APP_KEY` the package throws Laravel's `MissingAppKeyException` instead of signing with an empty key.

When you rotate `APP_KEY`, put the old key in `APP_PREVIOUS_KEYS` in `.env`. Forms that were open during the rotation then still validate.

## Expiry

A token in a plain form is part of the HTML, so a bot can load the page once and send the same token again and again. Plain forms therefore expire after `maximum_fill_seconds`, one day by default. A visitor who submits an older form gets "This form has expired. Please try again." and a fresh token on the next page load.

Livewire forms don't expire. Their start time is protected by Livewire's checksum instead, and a visitor who leaves a tab open overnight would otherwise lose what they typed.

## What it does not stop

A honeypot stops cheap, automated spam. It does not stop:

- a person typing spam by hand;
- a bot that runs a real browser, skips hidden fields and waits a few seconds;
- a bot that loads the form once and submits it many times. A plain form's token stays valid until it expires, and a Livewire form has no expiry.

Add rate limiting for that last case. For a plain form, use Laravel's `throttle` middleware on the route that receives the form. `routes/web.php`:

```php
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1');
```

A Livewire form submits to Livewire's own update route, so middleware on your page route does not count its submits. Use Laravel's `RateLimiter` inside the action instead:

```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

public function submit(): void
{
    $key = 'contact-form:'.request()->ip();

    if (RateLimiter::tooManyAttempts($key, 5)) {
        throw ValidationException::withMessages(['email' => 'Too many messages. Try again in a minute.']);
    }

    RateLimiter::hit($key, 60);

    $this->validateHoneypot();

    // ...
}
```

When that is not enough, add a CAPTCHA such as Cloudflare Turnstile on top. [Compared to alternatives](comparison.md) helps you choose.
