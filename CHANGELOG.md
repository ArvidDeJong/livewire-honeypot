# Changelog

All notable changes to **darvis/livewire-honeypot** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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