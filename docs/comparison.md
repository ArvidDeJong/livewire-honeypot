---
title: Compared to alternatives
nav_order: 7
---

# Compared to alternatives

There is no single best spam protection. This page helps you pick, including when this package is **not** the right choice.

## spatie/laravel-honeypot

[spatie/laravel-honeypot](https://github.com/spatie/laravel-honeypot) is the best-known Laravel honeypot: mature, widely used and well maintained. Both packages use a bait field and a time check. They differ in how a blocked submission is handled and in what they focus on.

| | darvis/livewire-honeypot | spatie/laravel-honeypot |
| --- | --- | --- |
| Blade component | `<x-honeypot />` | `<x-honeypot />` and `@honeypot` |
| Livewire | Trait; the start time and token are locked properties | Trait plus a `HoneypotData` property |
| Plain forms | Validate in the controller with `HoneypotService` | `ProtectAgainstSpam` middleware, per route or global |
| Inertia / JavaScript forms | Render the values from `generate()` yourself | Documented, with Vue examples |
| Blocked submission | Validation error the visitor can see, plus a `SpamBlocked` event | Blank page by default; a custom `SpamResponder` can change that |
| Bait field name | Generated from innocuous words (`referral_3f9a`), with ignore attributes for password managers | `my_name` with a random suffix |
| Default minimum time | 5 seconds | 1 second |
| Content Security Policy without inline styles | Not supported yet | `with_csp` option with spatie/laravel-csp |
| Turn off per environment | Set `minimum_fill_seconds` to 0 (the bait check stays) | `HONEYPOT_ENABLED=false` |

**Choose spatie** when you want one middleware for many forms or for the auth routes, when you use Inertia, or when your CSP forbids inline styles.

**Choose this package** when your forms are mostly Livewire, when a real visitor who trips the check should get a message instead of a blank page, or when you want to log blocked attempts through an event.

## Cloudflare Turnstile, reCAPTCHA and hCaptcha

A CAPTCHA service scores the visitor's browser with JavaScript and signals that only the provider sees. It stops far more advanced bots than a honeypot. The cost:

- **An external service.** Every visitor loads a third-party script. With reCAPTCHA and hCaptcha that raises privacy questions under the GDPR, and you may need consent first. Turnstile collects less, but is still a third party.
- **Friction.** Invisible modes usually pass without a puzzle, but some visitors still get a challenge, or get blocked on a VPN or with strict privacy settings.
- **A dependency.** When the service is slow or down, your form is too.

A honeypot has none of these costs and stops the cheap bots that send most form spam.

## Which one when

| Situation | Suggestion |
| --- | --- |
| Contact or quote form on a small to medium site | A honeypot is usually enough |
| Livewire forms | This package |
| Many classic forms, auth routes, Inertia | spatie/laravel-honeypot |
| Registration, login, or a form that sends mail to the address entered | Honeypot **and** rate limiting; add Turnstile if abuse continues |
| Targeted attacks, or bots that run a real browser | Turnstile or another CAPTCHA, with a honeypot in front of it |

Honeypot packages can be combined with a CAPTCHA. The honeypot stops the cheap bots without sending anything to the CAPTCHA provider.
