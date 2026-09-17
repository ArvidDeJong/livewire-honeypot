---
title: How it works
nav_order: 2
---

# How it works

A submission passes three checks, in this order. The first one that fails stops the submission with a validation error and a [`SpamBlocked` event](configuration.md#events).

| Check | Fails when | Message | Event reason |
| --- | --- | --- | --- |
| Bait field | the hidden field was filled in or not submitted | `spam_detected` | `FIELD_FILLED` |
| Payload | start time or token is missing, too short or has a wrong signature | `spam_detected` | `INVALID_PAYLOAD` |
| Time trap | fewer than `minimum_fill_seconds` passed since the form was loaded | `submitted_too_quickly` | `SUBMITTED_TOO_QUICKLY` |

## The bait field

Bots that fill in every input also fill in the bait. Visitors never see it:

- It is hidden like a screen-reader-only element (1×1 px, clipped) and marked `aria-hidden` with `tabindex="-1"`, so keyboard and screen reader users skip it too. Off-screen tricks like `left:-10000px` are easy for bots to recognise.
- Its name is generated, for example `referral_3f9a`. A fixed name like `hp_website` is simple for a bot to skip, and a name with "website", "email" or "company" in it gets filled in by browser autofill. That would block a real visitor.
- `autocomplete="off"` plus `data-1p-ignore`, `data-lpignore`, `data-bwignore` and `data-form-type="other"` keep 1Password, LastPass, Bitwarden and Dashlane away from it.

## The start time

Where the start time lives decides whether a bot can fake it.

- **Livewire:** `hp_started_at` and `hp_token` are `#[Locked]` properties. They live in the component snapshot that Livewire checksums, and a request that changes them is rejected.
- **Plain forms:** the time is inside `hp_token` as `random.timestamp.signature`, signed with HMAC-SHA256 and your `APP_KEY`. A changed timestamp breaks the signature. Rotating `APP_KEY` invalidates forms that were open at that moment.

## What it does not stop

A honeypot stops cheap, automated spam. It does not stop:

- a person typing spam by hand;
- a bot that runs a real browser, skips hidden fields and waits a few seconds;
- a bot that loads the form once and replays the same token many times. Add rate limiting:

```php
Route::get('/contact', ContactForm::class)->middleware('throttle:10,1');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1');
```

When that is not enough, add a CAPTCHA such as Cloudflare Turnstile on top.
