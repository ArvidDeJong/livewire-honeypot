---
title: Plain forms and controllers
nav_order: 4
---

# Plain forms and controllers

Outside Livewire, `<x-honeypot />` renders the bait field and a signed `hp_token`. Validate it with `HoneypotService` in the controller.

```blade
<form method="POST" action="/contact">
    @csrf

    <input type="email" name="email">
    <x-honeypot />

    <button type="submit">Send</button>
</form>
```

```php
use Darvis\LivewireHoneypot\Services\HoneypotService;
use Illuminate\Http\Request;

public function store(Request $request, HoneypotService $honeypot)
{
    $honeypot->validate($request->all());

    $data = $request->validate(['email' => 'required|email']);

    // process the form ...
}
```

`validate()` throws a `ValidationException`. Laravel redirects back with the error under the `field_name` key (default `hp_website`), and `<x-honeypot />` shows it. A JSON request gets a 422 response.

A custom minimum time for one form:

```php
$honeypot->validate($request->all(), minimumSeconds: 10);
```

## Rendering the inputs yourself

When you can't use the Blade component, for example in an Inertia or JavaScript form:

```php
$fields = app(HoneypotService::class)->generate();
// ['hp_website' => '', 'hp_started_at' => 1789657867, 'hp_token' => 'Xy3k….1789657867.9f2c…']
```

- Send `hp_token` back unchanged.
- Render the bait as a hidden text input named after `field_name`. Pick a `field_name` that browser autofill does not recognise; avoid "website", "url", "email", "name" and "company".
- `hp_started_at` is only there for backward compatibility. The service reads the start time from the signed token and ignores this field.

## Caching

The token holds the time the page was rendered. When full pages are cached, every visitor gets the same old token and the time trap always passes. The bait field still works. Exclude form pages from the page cache, or render the form with Livewire.
