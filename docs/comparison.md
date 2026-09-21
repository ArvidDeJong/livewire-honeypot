---
title: "Compared to alternatives"
nav_order: 9
description: "How darvis/livewire-honeypot differs from spatie/laravel-honeypot and from CAPTCHA services such as Cloudflare Turnstile and reCAPTCHA, and when to pick which."
---

# Compared to alternatives

This page helps you pick a spam protection, including when this package is **not** the right choice.

## spatie/laravel-honeypot

[spatie/laravel-honeypot](https://github.com/spatie/laravel-honeypot) is another Laravel honeypot package. Both packages use a bait field and a time check. The right-hand column is taken from its README and its default config file, as published in September 2026. Check its documentation for the current state.

| | darvis/livewire-honeypot | spatie/laravel-honeypot |
| --- | --- | --- |
| Blade component | `<x-honeypot />` | `<x-honeypot />` and the `@honeypot` directive |
| Livewire | `HasHoneypot` trait; the start time and token are locked properties | `UsesSpamProtection` trait plus a `HoneypotData` property |
| Plain forms | Call `HoneypotService::validate()` in the controller | `ProtectAgainstSpam` middleware, per route or global |
| Inertia / JavaScript forms | Render the values from `generate()` yourself | Documented, with Vue examples |
| Blocked submission | A validation error the visitor sees | A blank page by default; a custom `SpamResponder` can change that |
| Event | `SpamBlocked` with the reason, the IP address and the Livewire component | `SpamDetectedEvent` with the request |
| Bait field name | Generated from neutral words (`referral_3f9a`), with ignore attributes for password managers | `my_name` with a random suffix by default |
| Default minimum time | 5 seconds | 1 second |
| Content Security Policy without inline styles | Automatic with `Vite::useCspNonce()`, or a `nonce` attribute | `with_csp` option, which requires spatie/laravel-csp |
| Switch off | Set `minimum_fill_seconds` to `0` for the time check; the bait check has no switch | `HONEYPOT_ENABLED=false` |

Both packages register a Blade component named `<x-honeypot />`. Install one of them, not both.

**Choose spatie/laravel-honeypot** when you want one middleware for many forms or for the authentication routes, when you use Inertia, or when you already use spatie/laravel-csp.

**Choose this package** when your forms are mostly Livewire, or when a real visitor who trips a check must get a message instead of a blank page.

## CAPTCHA services: Cloudflare Turnstile, reCAPTCHA, hCaptcha

A CAPTCHA service judges the visitor in the browser, with a script from the provider. It is meant for bots a honeypot cannot stop, such as bots that run a real browser. What you take on with it:

- **A third party.** Every visitor loads a script from the provider. Check what that means for your privacy statement and consent.
- **Possible friction.** A visitor can get a challenge to solve.
- **A dependency.** The form needs the provider's script and API to work.

This package has none of these: no script, no cookie, no request to another service. What it [does not stop](how-it-works.md#what-it-does-not-stop) is where a CAPTCHA comes in.

## Which one when

| Situation | Suggestion |
| --- | --- |
| Contact or quote form that gets automated spam | Start with a honeypot and rate limiting |
| Livewire forms | This package |
| Many classic forms, authentication routes, Inertia | spatie/laravel-honeypot |
| Registration, login, or a form that sends mail to the address entered | A honeypot **and** rate limiting; add a CAPTCHA if abuse continues |
| Targeted attacks, or bots that run a real browser | A CAPTCHA, with a honeypot in front of it |

A honeypot can be combined with a CAPTCHA. Validate the honeypot first: a submission it blocks never has to be verified with the CAPTCHA provider.
