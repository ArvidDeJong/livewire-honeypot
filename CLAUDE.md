# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package overview

`darvis/livewire-honeypot` is a Laravel package (PHP 8.2+, Laravel 11/12/13, Livewire 3/4) that protects forms with a hidden bait field and a minimum fill time. Host apps consume it via Composer; this repo only contains the library.

- Namespace: `Darvis\LivewireHoneypot\` → `src/`
- Service provider auto-registered via `extra.laravel.providers` in [composer.json](composer.json)
- Config key: `livewire-honeypot`

## Commands

```bash
composer test                 # Pest suite
vendor/bin/pest --filter "configured field name"
composer lint                 # Pint (check only); composer format fixes
composer analyse              # Larastan, level 5
```

CI (`.github/workflows/tests.yml`) runs PHP 8.2–8.4 × Laravel 11/12/13 × lowest/stable, plus Pint and Larastan. It turns off Composer's advisory blocking, because every Laravel 11 release has an open advisory.

## Architecture

- [HoneypotService::check()](src/Services/HoneypotService.php) holds all validation logic. `validate()` (controllers) and `HasHoneypot::validateHoneypot()` (Livewire) both delegate to it. Don't duplicate checks in the trait.
- Every rejection goes through `reject()`, which dispatches `Events\SpamBlocked` and throws a `ValidationException`.
- [HasHoneypot](src/Traits/HasHoneypot.php) marks `hp_started_at` and `hp_token` as `#[Locked]`. Before 1.1.0 the blade bound them with `wire:model`, so a client could set its own start time. Never bind them again. Tests travel in time instead of `->set()`.
- `field_name` sets the HTML `name` of the bait input and the data key the service reads. The Livewire property stays `hp_website`, because a public property name cannot come from config.
- [honeypot.blade.php](resources/views/components/honeypot.blade.php) renders the error outside the hidden wrapper. Livewire shares `$errors` with views while rendering, so the anonymous component can read it.

## Conventions

- `resources/boost/` holds the Laravel Boost guideline and skill that host apps receive. Update them when public behaviour or config changes.
- Keep the public API compatible within 1.x: `generate()`, `validate()`, `validateHoneypot()`, `resetHoneypot()` and the `hp_*` properties.
- A change a site owner notices (messages, error keys, defaults) is a minor release, not a patch.
- Everything is in English: code, comments, messages, README and CHANGELOG. Translations live in `resources/lang/{en,nl}`; add every new key to both.
