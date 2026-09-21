---
title: "Home"
nav_order: 1
description: "darvis/livewire-honeypot stops form spam in Livewire 3 and 4 components and plain Laravel forms with a hidden bait field and a time check, without a CAPTCHA."
permalink: /
---

# Livewire Honeypot

`darvis/livewire-honeypot` is a Laravel package that stops automated form spam without a CAPTCHA. It adds a hidden field that only bots fill in (the honeypot), and it refuses a form that is submitted faster than a person can type (the time trap).

It is for Laravel developers who have a contact, quote or signup form, built as a [Livewire](https://livewire.laravel.com) component or as a plain Blade form with a controller. The package sets no cookies, loads no JavaScript and sends nothing to another service. All checks run on your own server.

![A contact form as a visitor sees it, next to the same form as a bot sees it with the hidden field revealed](assets/images/visitor-vs-bot.png)

## What it does not do

- It does not stop a person who types spam by hand.
- It does not stop a bot that runs a real browser, skips hidden fields and waits a few seconds.
- It does not limit how often a form is submitted. Add Laravel's `throttle` middleware for that.
- It has no middleware of its own. You call one method in the Livewire action or in the controller.

[How it works](how-it-works.md#what-it-does-not-stop) explains these limits.

## Requirements

- PHP 8.2 or higher
- Laravel 11, 12 or 13
- Livewire 3 or 4. Composer installs Livewire together with the package, also when you only protect plain forms.
- An `APP_KEY` in `.env`. A new Laravel application has one; `php artisan key:generate` creates it.

## Install

```bash
composer require darvis/livewire-honeypot
```

Then, in a Livewire form:

1. Add the `HasHoneypot` trait to the component.
2. Put `<x-honeypot />` inside the `<form>`.
3. Call `$this->validateHoneypot()` in the method that handles the submit.

[Installation](installation.md) has the full steps and a way to check that it works. There is nothing to publish and no migration to run.

## All pages

- [Installation](installation.md): requirements, the steps, and how to check that the honeypot works
- [Livewire forms](livewire.md): a complete contact form, single-file and multi-file components, form objects
- [Plain forms and controllers](plain-forms.md): a Blade form with a controller, expiry, key rotation, JavaScript forms
- [Configuration and events](configuration.md): the four settings, translations, Content Security Policy, the `SpamBlocked` event
- [Testing your forms](testing.md): test a protected component or controller in your own application
- [How it works](how-it-works.md): the checks in order, why the field is hidden this way, what a honeypot does not stop
- [Troubleshooting](troubleshooting.md): real visitors are blocked, nothing is blocked, and every error message with its cause
- [Compared to alternatives](comparison.md): spatie/laravel-honeypot and CAPTCHA services
- [Your honeypot may be blocking real visitors](autofill-blocks-real-visitors.md): how browser autofill fills hidden fields
- [Honeypot autofill test](honeypot-autofill-test.md): see whether your browser fills hidden fields, and check your own form
- [FAQ](faq.md): short answers to common questions
