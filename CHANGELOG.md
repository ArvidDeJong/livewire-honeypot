# Changelog

All notable changes to **darvis/livewire-honeypot** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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