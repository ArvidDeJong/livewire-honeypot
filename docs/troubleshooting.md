---
title: "Troubleshooting"
nav_order: 8
description: "Fix darvis/livewire-honeypot by symptom: real visitors get Spam detected, nothing is blocked, the form has expired, the hidden field shows, a setting is ignored."
---

# Troubleshooting

Each section is a symptom, its causes and the fix. The messages are quoted exactly as the package and Laravel show them, so you can search for them.

Start by [logging the `SpamBlocked` event](configuration.md#logging-blocked-submissions). Its `reason` tells you which check refused the submission.

## Real visitors get "Spam detected." {#real-visitors-get-spam-detected}

`Spam detected.` means one of two things: the bait field was filled in or missing (reason `field_filled`), or the start time or token was invalid (reason `invalid_payload`).

| Cause | Fix |
| --- | --- |
| **Plain form, every submit fails.** The controller calls `validate($request->all())`. Laravel's `ConvertEmptyStringsToNull` middleware turned the empty bait into `null`, and `null` counts as "not submitted" | Pass `array_map(fn ($value) => $value ?? '', $request->all())`. See [Plain forms](plain-forms.md#why-the-array_map-line-is-there) |
| **Plain form:** `<x-honeypot />` is outside the `<form>` tag, or a script sends only some of the fields, so the bait and `hp_token` never arrive | Move `<x-honeypot />` inside the form. In a JavaScript form, send the bait and `hp_token` along |
| **Plain form:** `APP_KEY` was rotated, and forms that were already open carry a token signed with the old key | Put the old key in `APP_PREVIOUS_KEYS` in `.env` |
| **Livewire form posts to a controller,** or the controller of a Livewire form calls `HoneypotService::validate()`. A Livewire form has no `hp_token` | Call `$this->validateHoneypot()` in the component. Use `validate()` only for plain forms |
| **Livewire:** the `HasHoneypot` trait is on a form object. Livewire does not run the mount hook there, so the start time stays `0` | Put the trait on the component. See [Form objects](livewire.md#form-objects) |
| **You render the bait input yourself,** under a name or label that browser autofill recognises, such as the default `hp_website` with a "Website" label. Autofill fills it for real visitors | Use `<x-honeypot />`, or set `HONEYPOT_FIELD_NAME` to a neutral word and use a neutral label. Check the form with the [autofill test](honeypot-autofill-test.md) |
| **A published view or translation from before 1.2.0** still uses the name `hp_website` and the label "Website (leave empty)" | Delete `resources/views/vendor/livewire-honeypot` and `lang/vendor/livewire-honeypot`, or publish them again. See [Changing the HTML](configuration.md#changing-the-html) |

[Your honeypot may be blocking real visitors](autofill-blocks-real-visitors.md) explains the autofill problem.

## Real visitors get "Form submitted too quickly." {#real-visitors-get-form-submitted-too-quickly}

| Cause | Fix |
| --- | --- |
| The form is short, and a visitor with autofill finishes it within `minimum_fill_seconds` (default 5) | Lower `HONEYPOT_MINIMUM_FILL_SECONDS`, for example to `2`. For one plain form: `validate($data, minimumSeconds: 2)` |
| Livewire: a visitor sends a second message right after the first. `resetHoneypot()` restarted the timer | This is intended. Lower the minimum if it gets in the way |
| A test submits at once | Travel in time or set the minimum to `0`. See [Testing your forms](testing.md) |

## Visitors get "This form has expired. Please try again." {#visitors-get-this-form-has-expired}

Only plain forms expire, after `maximum_fill_seconds` (default `86400`, one day).

| Cause | Fix |
| --- | --- |
| The page was open for longer than a day | Nothing. The visitor reloads and submits again |
| A full-page cache or CDN serves the same HTML, with the same token, for longer than `maximum_fill_seconds` | Exclude pages with a form from the cache, or build the form in Livewire. `HONEYPOT_MAXIMUM_FILL_SECONDS=0` turns expiry off, but see [Page caching](plain-forms.md#page-caching) |

## Nothing is blocked {#nothing-is-blocked}

Test it first: submit the form within five seconds. You should see "Form submitted too quickly.".

| Cause | Fix |
| --- | --- |
| `validateHoneypot()` (Livewire) or `HoneypotService::validate()` (controller) is never called. The trait and `<x-honeypot />` alone check nothing | Add the call in the method that handles the submit, before you process the data |
| `minimum_fill_seconds` is `0`, in `.env` or in a published `config/livewire-honeypot.php` | Set it back to `5` and run `php artisan config:clear` |
| Plain form on a cached page: the token is old, so the time check always passes | Exclude the page from the cache. See [Page caching](plain-forms.md#page-caching) |
| Livewire: `<x-honeypot wire:model="...">` binds the bait to another property, while `validateHoneypot()` reads `hp_website` | Remove the attribute, or run the check yourself. See [Binding the bait to another property](livewire.md#binding-the-bait-to-another-property) |
| Livewire: `resetHoneypot()` is not called after a successful submit, so later submits from the same page skip the wait | Call `$this->resetHoneypot()` after processing |
| The spam comes from a person, or from a bot that runs a real browser and waits | A honeypot does not stop that. See [What it does not stop](how-it-works.md#what-it-does-not-stop) |

## A changed setting has no effect {#a-changed-setting-has-no-effect}

| Cause | Fix |
| --- | --- |
| The config is cached, so `.env` is not read | `php artisan config:clear`, or `php artisan config:cache` again |
| A published `config/livewire-honeypot.php` has a hard-coded value that wins over the package default | Edit the published file, or delete it to use `.env` and the defaults |
| You set `token_min_length` | It was removed in 1.4.0 and is ignored |
| You changed `field_name` for a Livewire form | Livewire forms always use `hp_website`. `field_name` is for plain forms |

## The hidden field is visible {#the-hidden-field-is-visible}

Visitors see a field labelled "Leave this field empty". Your Content Security Policy blocks the inline `style` attribute that hides it. Give the component a nonce: see [Content Security Policy](configuration.md#content-security-policy).

## The error message shows twice, or not at all {#the-error-message-shows-twice-or-not-at-all}

| Symptom | Cause and fix |
| --- | --- |
| Twice | The view has its own `@error('hp_website')`. Remove it; `<x-honeypot />` shows the error |
| Not at all, plain form | The form page is rendered outside the `web` middleware group, so the view has no `$errors` variable. Put the form page and the POST route in `routes/web.php` |
| Not at all, custom `field_name` | You read the error under `hp_website` while plain forms report it under `field_name`. Let `<x-honeypot />` show it |
| Not at all, published view | The view was published before 1.1.0 and has no error block. Delete or republish it |

## Cannot update locked property: [hp_started_at] {#cannot-update-locked-property}

Livewire throws `CannotUpdateLockedPropertyException` with this message, or with `[hp_token]`.

| Cause | Fix |
| --- | --- |
| A test calls `->set('hp_started_at', ...)` or `->set('hp_token', ...)` | Use `$this->travel(10)->seconds()`. See [Testing your forms](testing.md) |
| Your own view binds `hp_started_at` or `hp_token` with `wire:model` | Remove the binding. Only the bait, `hp_website`, is bound, and `<x-honeypot />` does that |
| A bot tried to change the start time | Nothing. This is the protection working |

## No application encryption key has been specified. {#no-application-encryption-key-has-been-specified}

Laravel's `MissingAppKeyException`. The package signs tokens with `APP_KEY` and refuses to sign with an empty key. Run `php artisan key:generate`, then `php artisan config:clear`.

## Unable to locate a class or view for component [honeypot]. {#unable-to-locate-a-class-or-view-for-component-honeypot}

Laravel does not know the `<x-honeypot />` component, so the service provider is not loaded.

1. Check that the package is installed: `composer show darvis/livewire-honeypot`.
2. Run `php artisan package:discover`, then `php artisan view:clear`.
3. If `composer.json` lists the package under `extra.laravel.dont-discover`, register `Darvis\LivewireHoneypot\HoneypotServiceProvider::class` in `bootstrap/providers.php`.

spatie/laravel-honeypot also registers a Blade component named `<x-honeypot />`. Install one of the two packages, not both.

## Still stuck?

Open an [issue](https://github.com/ArvidDeJong/livewire-honeypot/issues) with the package, Laravel and Livewire versions and the `reason` of the `SpamBlocked` event. Found a way around the honeypot? Report it privately, as described in the [security policy](https://github.com/ArvidDeJong/livewire-honeypot/blob/main/SECURITY.md).
