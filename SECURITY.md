# Security policy

This package exists to keep spam out of forms, so a way around its checks counts as a security issue.

## Supported versions

Only the latest minor release of 1.x receives security fixes. Upgrade before reporting.

## Reporting a vulnerability

Please do **not** open a public issue. Report it privately instead:

- via [GitHub private vulnerability reporting](https://github.com/ArvidDeJong/livewire-honeypot/security/advisories/new), or
- by email to info@arvid.nl.

Include the package version, Laravel and Livewire versions, and the steps or request that get past the honeypot.

You will get a reply within a week. Once a fix is released, the advisory is published and you are credited, unless you prefer not to be.

## Out of scope

A honeypot does not stop a person typing spam by hand, or a bot that runs a real browser, skips hidden fields and waits long enough. That is a known limit, described in [How it works](https://arviddejong.github.io/livewire-honeypot/how-it-works.html#what-it-does-not-stop), not a vulnerability.
