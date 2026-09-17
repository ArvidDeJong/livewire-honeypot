# Contributing

Contributions are welcome: bug reports, fixes, documentation and ideas.

## Before you start

- **Bugs:** open an [issue](https://github.com/ArvidDeJong/livewire-honeypot/issues/new/choose) with the steps to reproduce.
- **Features:** open an issue first. This package stays small on purpose, so let's agree a feature fits before you build it.
- **Security issues:** don't open an issue; see [SECURITY.md](SECURITY.md).

## Development

```bash
git clone https://github.com/ArvidDeJong/livewire-honeypot.git
cd livewire-honeypot
composer install

composer test      # Pest
composer lint      # Pint, check only (composer format fixes)
composer analyse   # Larastan, level 5
```

CI runs the tests on PHP 8.2-8.4 with Laravel 11, 12 and 13, on the lowest and the latest dependencies.

## Pull requests

- Add or update tests for every change in behaviour.
- Keep the public API compatible within 1.x: `generate()`, `validate()`, `validateHoneypot()`, `resetHoneypot()` and the `hp_*` properties.
- Write code, comments and messages in English. New translation keys go into both `resources/lang/en` and `resources/lang/nl`.
- Update `docs/`, `CHANGELOG.md` (under `Unreleased`) and `resources/boost/` when users will notice the change.
- The documentation in `docs/` is also the website. Don't write `{{ }}` or `{% %}` there; Jekyll would render it.

## Code of conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md).
