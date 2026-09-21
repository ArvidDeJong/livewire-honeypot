# Changelog

All notable changes to **darvis/livewire-honeypot** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.6.0] - 2026-09-21
### Fixed
- Plain forms: every real submission was rejected with "Spam detected." in an application that runs Laravel's `ConvertEmptyStringsToNull` middleware, which a default Laravel application does. The middleware turns the empty hidden field into `null`, and `HoneypotService::validate()` read `null` as "the field was not submitted". A hidden field that is present but `null` now counts as empty; a field that is absent is still rejected, and a filled field is still spam. After upgrading: `validate($request->all())` works as documented. If you added the `array_map(fn ($value) => $value ?? '', $request->all())` workaround from the 1.5.1 docs, you can remove it; leaving it in does no harm. Livewire forms were never affected

### Security
- Livewire: a form that bound the hidden field to another property with `<x-honeypot wire:model="..." />` was not protected by the hidden field, because `validateHoneypot()` always read `hp_website`. `validateHoneypot()` now takes the same two values as the component. After upgrading: search your views for `<x-honeypot wire:model=`; for each one, pass the same path in the component, `$this->validateHoneypot(model: 'contact.hp_website', errorKey: 'contact.hp_website')`, and make sure that property starts as an empty string. Forms that use `<x-honeypot />` without attributes need no change

### Added
- `validateHoneypot(?string $model = null, ?string $errorKey = null)`: optional arguments that mirror the `wire:model` and `error-key` attributes of `<x-honeypot />`. Both default to `hp_website`, so existing calls behave as before. A component that defines its own `validateHoneypot()` keeps working
- A feature test that sends plain forms through the real middleware stack, without `withoutMiddleware()`

### Changed
- The `HasHoneypot` trait on a Livewire form object, or on any class that is not a component, now throws a `LogicException` from `validateHoneypot()` that says to move the trait to the component. Livewire runs no mount hook there, so the honeypot never had a start time and every submit was answered with "Spam detected.". After upgrading: nothing, unless you see this exception; then move the trait and the `validateHoneypot()` call to the component. A component never gets this exception

## [1.5.1] - 2026-09-21
### Added
- Docs: an [Installation](https://arviddejong.github.io/livewire-honeypot/installation.html) page with a "Check that it works" section, and a [Troubleshooting](https://arviddejong.github.io/livewire-honeypot/troubleshooting.html) page organised by symptom, with every message quoted literally
- Docs: complete, copy-paste contact forms for Livewire and for a controller, with the file each block belongs to; a test for a blocked submission on the testing page
- `tests/DocsSiteTest.php` checks that relative links resolve, that the home page links to every page, and that the docs quote the English messages literally

### Fixed
- Docs, README and the Boost guideline and skill told plain forms to call `HoneypotService::validate($request->all())`. In an application with Laravel's default `ConvertEmptyStringsToNull` middleware the empty bait arrives as `null`, which the service rejects as a missing field, so every real submission got "Spam detected.". The documented call is now `validate(array_map(fn ($value) => $value ?? '', $request->all()))`. The package code is unchanged
- Docs said to pass `wire:model` and `error-key` to `<x-honeypot />` "when the bait property lives somewhere else". `validateHoneypot()` always reads `$this->hp_website` and reports under `hp_website`, so such a form blocked nothing; the page now shows the `HoneypotService::check()` call that is needed
- Docs suggested `throttle` middleware on the Livewire page route as rate limiting. Livewire submits go to Livewire's own update route, so that limited page loads only; the page now uses `RateLimiter` inside the action
- The event example logged to `Log::channel('spam')`, a channel a Laravel application does not have; it now uses `Log::info()`
- The Content Security Policy example used `csp_nonce()`, which spatie/laravel-csp 3 no longer has; it now uses `app('csp-nonce')`
- The event test example submitted an empty form, which fails on the form's own validation before the honeypot runs, so `SpamBlocked` was never dispatched
- The comparison and the FAQ presented the `SpamBlocked` event as a difference with spatie/laravel-honeypot, which dispatches `SpamDetectedEvent`. Claims about other packages and CAPTCHA services that their documentation does not back were removed
- Requirements said Livewire is "optional for plain forms". Composer always installs Livewire with the package; using it is optional
- `field_name` was described without saying that it only applies to plain forms; a Livewire form always uses `hp_website`
- `CLAUDE.md` named `HoneypotService` as the one place that reads the config; since 1.5.0 that is `HoneypotConfig`. `CONTRIBUTING.md` asked for new translation keys in `en` and `nl`; the package ships five locales

### Changed
- README follows the shared order (features, requirements, installation, one quick start, documentation, Laravel Boost, testing) and links to the new pages

## [1.5.0] - 2026-09-20
### Added
- `HoneypotConfig` with named accessors is the one place that reads the package config. Every default is written down once, so `HoneypotService` and the config file cannot quietly disagree about what it is. The public `fieldName()`, `minimumFillSeconds()` and `maximumFillSeconds()` on `HoneypotService` are unchanged and now delegate
- Tests for the exact time boundaries, the config defaults, the derived bait name and wrapper class, the error key with a custom `field_name`, the merged config and the component registration. Mutation score went from 69% to 88%; `composer mutate` runs it

### Changed
- The config keys are in alphabetical order. No key, default or behaviour changed

## [1.4.0] - 2026-09-17
### Added
- Plain forms expire after `maximum_fill_seconds` (default one day, `0` disables), so a token scraped from the page can't be replayed forever. New `form_expired` message in all five languages and `SpamBlocked::EXPIRED`. Livewire forms don't expire
- Tokens signed with a key in `APP_PREVIOUS_KEYS` still validate, including their bait name, so rotating `APP_KEY` no longer rejects open forms
- `HoneypotService::fieldName()`, `minimumFillSeconds()` and `maximumFillSeconds()`
- Docs site: [honeypot autofill test](https://arviddejong.github.io/livewire-honeypot/honeypot-autofill-test.html), a page to test whether a browser or password manager fills hidden bait fields, and to check the HTML of any form for risky honeypot fields

### Fixed
- A plain form inside a Livewire component that doesn't use `HasHoneypot`, such as a newsletter form posting to a controller, was rendered in Livewire mode without a token, so every submission was rejected
- Without an `APP_KEY`, tokens were signed with an empty key and could be forged; the package now throws `MissingAppKeyException`

### Changed
- `<x-honeypot />` is a class component; the view only holds markup. Republish the view if you published it before 1.4.0
- The Livewire `hp_token` is now a signed token like in plain forms, and tokens are no longer length-checked. The `token_min_length` config option is removed and ignored if still present
- Larastan runs at level 8; the tests no longer repeat the config defaults

## [1.3.0] - 2026-09-17
### Added
- Content Security Policy support: with `Vite::useCspNonce()` or `<x-honeypot nonce="..." />`, the field is hidden through a nonced `<style>` block instead of an inline style attribute
- German, French and Spanish translations
- `HoneypotService::wrapperClass()`
- Docs site: FAQ, a description per page, sitemap, `llms.txt`, and structured data for the package and the FAQ
- Documentation: comparison with spatie/laravel-honeypot, Turnstile and reCAPTCHA; article on how autofill makes honeypots block real visitors
- `CONTRIBUTING.md`, `SECURITY.md` with private vulnerability reporting, `CODE_OF_CONDUCT.md`, issue and pull request templates, Dependabot
- README badges and an image of what a visitor and a bot see; social preview image for the docs site

## [1.2.0] - 2026-09-17
### Added
- `<x-honeypot />` also works in plain (non-Livewire) forms: it renders the bait and a signed `hp_token` for `HoneypotService::validate()`
- `HoneypotService::token()`, `startedAtFromToken()` and `baitName()`
- Documentation site on GitHub Pages (https://arviddejong.github.io/livewire-honeypot/), built from `docs/`: how it works and its limits, Livewire (class, single-file and multi-file components, form objects), plain forms, configuration and events, testing

### Fixed
- Browser autofill and password managers could fill the bait field (`hp_website`, labelled "Website") and block real visitors. The field now gets a generated name such as `referral_3f9a`, a neutral label, and ignore attributes for 1Password, LastPass, Bitwarden and Dashlane
- The time trap in plain forms could be bypassed by posting an old `hp_started_at`. The start time now comes from a token signed with `APP_KEY`
- `<x-honeypot />` no longer fails with "Undefined variable $errors" in views rendered without the web middleware

### Changed
- `HoneypotService::validate()` rejects tokens without a valid signature and ignores a posted `hp_started_at`. Forms built from `generate()` keep working; forms that were open during the deploy, or during an `APP_KEY` rotation, are rejected once
- `HoneypotService::generate()` returns a signed `hp_token`; `token_length` now sets the length of its random part
- The bait field is hidden with screen-reader-only CSS instead of being moved off screen, and the `hp-field` class is gone. Update a published view if you styled that class
- The `honeypot_label` translation is now "Leave this field empty" / "Laat dit veld leeg"
- The README is shorter and links to `docs/`

## [1.1.0] - 2026-09-17
### Added
- `SpamBlocked` event with `reason`, `ip` and `component`, dispatched before a submission is rejected
- `<x-honeypot />` shows the honeypot error outside the hidden field; `error-key` attribute to change the key
- `HoneypotService::check()`, shared by the service and the trait
- GitHub Actions: PHP 8.2-8.4 x Laravel 11/12/13 x lowest/stable, plus Pint and Larastan; `composer lint`, `format` and `analyse` scripts
- Laravel Boost guideline and `livewire-honeypot-development` skill in `resources/boost`
- `phpunit.xml.dist`, `.gitattributes` export-ignore, `CLAUDE.md`

### Fixed
- The time trap could be bypassed: `hp_started_at` and `hp_token` were bound with `wire:model`, so a client could send its own start time. Both are now `#[Locked]` and no longer rendered
- `field_name` config had no effect; it now sets the bait input name and the key `HoneypotService` reads
- A visitor who submitted too quickly saw no message, because the error was only attached to the hidden field
- `minimum_fill_seconds` and the token lengths from `.env` are cast to integers

### Changed
- `HoneypotService::validate()` reports every failure under the bait field key with the `spam_detected` or `submitted_too_quickly` message, instead of Laravel's default messages on `hp_started_at` and `hp_token`
- The bait input uses `wire:model` instead of the deprecated `wire:model.lazy`
- Tests in host apps can no longer `->set('hp_started_at', ...)`; travel in time or set `minimum_fill_seconds` to 0
- Dev dependencies allow Testbench 9-11, Pest 3-4 and PHPUnit 11-13 so every supported Laravel version is tested
- README rewritten for Livewire 3/4 and Laravel 11-13

## [1.0.4] - 2026-03-21
### Changed
- Added support for Laravel 13

## [1.0.3] - 2025-11-05
### Changed
- Updated framework version constraints to support Laravel 12 and Livewire 4

## [1.0.2] - 2025-10-25
### Changed
- Renamed `initializeHasHoneypot()` method to `mountHasHoneypot()` to better align with Livewire 3's lifecycle hooks
- Method functionality remains unchanged, only the name has been updated for consistency

## [1.0.1] - 2025-10-25
### Added
- Configuration file with customizable settings:
  - `minimum_fill_seconds` (default: 5, was hardcoded to 3)
  - `field_name` (default: hp_website)
  - `token_min_length` (default: 10)
  - `token_length` (default: 24)
- Multilingual support with English and Dutch translations
- Translation keys for error messages and honeypot label
- Config publishing with `--tag=livewire-honeypot-config`
- Translation publishing with `--tag=livewire-honeypot-translations`
- Environment variable support for all config options

### Changed
- Minimum fill time increased from 3 to 5 seconds (configurable)
- Error messages now use translation keys instead of hardcoded strings
- Honeypot label text is now translatable
- Token length is now configurable (was hardcoded to 24)

### Improved
- Updated README with configuration and translation documentation
- All hardcoded values moved to config file

## [1.0.0] - 2025-10-23
### Added
- Initial release with:
  - Livewire Trait `HasHoneypot`
  - Service `HoneypotService`
  - Blade component `<x-honeypot />`
  - View publishing, Laravel auto‑discovery