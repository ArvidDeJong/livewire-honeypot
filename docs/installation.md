---
title: "Installation"
nav_order: 2
description: "Install darvis/livewire-honeypot with Composer, add it to a first form, and check in the terminal and the browser that the hidden field and the time check work."
---

# Installation

## Requirements

| What | Version |
| --- | --- |
| PHP | 8.2 or higher |
| Laravel | 11, 12 or 13 |
| Livewire | 3 or 4. Composer installs it with the package, also when you only use plain forms |
| `APP_KEY` | Must be set in `.env`. The package signs its tokens with it |

## Install the package

1. Require the package:

   ```bash
   composer require darvis/livewire-honeypot
   ```

   Laravel discovers the service provider by itself. It registers the `<x-honeypot />` Blade component, the config and the translations.

2. Check that `.env` has an `APP_KEY`. If the line is empty, run:

   ```bash
   php artisan key:generate
   ```

3. Protect a form. Pick the page that matches how the form is built:

   - [Livewire forms](livewire.md): the form is a Livewire component.
   - [Plain forms and controllers](plain-forms.md): the form posts to a route and a controller.

There is no migration, no middleware to register and nothing you must publish. The package needs no environment variables to start.

## Settings you can change later

All four settings are optional. Set them in `.env`, or publish the config file. [Configuration](configuration.md) describes each one.

| Env variable | Default | What it does |
| --- | --- | --- |
| `HONEYPOT_MINIMUM_FILL_SECONDS` | `5` | Seconds a visitor must take before submitting |
| `HONEYPOT_MAXIMUM_FILL_SECONDS` | `86400` | Seconds after which a plain form expires |
| `HONEYPOT_FIELD_NAME` | `hp_website` | Bait and error key for plain forms |
| `HONEYPOT_TOKEN_LENGTH` | `24` | Length of the random part of a token |

The publish commands, when you want to change a file:

```bash
php artisan vendor:publish --tag=livewire-honeypot-config
php artisan vendor:publish --tag=livewire-honeypot-translations
php artisan vendor:publish --tag=livewire-honeypot-views
```

## Check that it works

### 1. The component renders

Run this in the project folder. Tinker is the console that ships with a new Laravel application.

```bash
php artisan tinker --execute="echo Blade::render('<x-honeypot />');"
```

You should see HTML like this. The field name and the token differ on every run:

```html
<div aria-hidden="true" style="position:absolute!important;width:1px!important;...">
    <label>
        <span>Leave this field empty</span>
        <input type="text"
               name="remarks_1c50"
               value="" tabindex="-1"
               autocomplete="off"
               data-1p-ignore
               data-lpignore="true"
               data-bwignore
               data-form-type="other" />
    </label>
</div>
    <input type="hidden" name="hp_token" value="TAoQeeFnN1q5DKu4VljknxZr.1789987561.25fd018c...">
```

Something else?

- `Unable to locate a class or view for component [honeypot].`: the package is not discovered. See [Troubleshooting](troubleshooting.md#unable-to-locate-a-class-or-view-for-component-honeypot).
- `No application encryption key has been specified.`: `APP_KEY` is empty. See [Troubleshooting](troubleshooting.md#no-application-encryption-key-has-been-specified).

### 2. The form blocks a fast submit

1. Open the page with your protected form in a browser.
2. Fill in the form and submit it within five seconds.
3. The form shows **Form submitted too quickly.** and nothing is sent.
4. Wait five seconds and submit again. The form goes through.

If the fast submit goes through, the check is not running. See [Nothing is blocked](troubleshooting.md#nothing-is-blocked). If the second submit is refused too, see [Real visitors are blocked](troubleshooting.md#real-visitors-get-spam-detected).

You can write the same check as an automated test: see [Testing your forms](testing.md).
