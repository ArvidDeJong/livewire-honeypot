---
title: "Plain forms and controllers"
nav_order: 4
description: "Protect a plain Blade form that posts to a Laravel controller with x-honeypot and HoneypotService::validate(), plus expiry, APP_KEY rotation and JavaScript forms."
---

# Plain forms and controllers

Outside Livewire, `<x-honeypot />` renders the hidden bait field and a hidden `hp_token` input. The token holds the time the form was rendered, signed with your `APP_KEY`. In the controller, `HoneypotService::validate()` checks both.

## A complete contact form

`routes/web.php`:

```php
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::view('/contact', 'contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1');
```

`throttle:5,1` is Laravel's rate limiter: at most five submits per minute per visitor. The package itself does not limit how often a form is sent.

`resources/views/contact.blade.php`:

```blade
<form method="POST" action="/contact">
    @csrf

    <input type="email" name="email">
    @error('email') <p>Enter a valid email address.</p> @enderror

    <x-honeypot />

    <button type="submit">Send</button>

    @if (session('status'))
        <p>Thank you for your message.</p>
    @endif
</form>
```

`<x-honeypot />` must be inside the `<form>` tag, otherwise the browser does not send its fields. It also shows the honeypot error message.

`app/Http/Controllers/ContactController.php`:

```php
<?php

namespace App\Http\Controllers;

use Darvis\LivewireHoneypot\Services\HoneypotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request, HoneypotService $honeypot): RedirectResponse
    {
        // Laravel turns an empty field into null. Turn it back, or the empty bait counts as missing.
        $honeypot->validate(array_map(fn ($value) => $value ?? '', $request->all()));

        $data = $request->validate(['email' => 'required|email']);

        // Send the mail or store $data here.

        return back()->with('status', 'sent');
    }
}
```

`validate()` throws a `ValidationException` when a check fails. Laravel then redirects back to the form, and `<x-honeypot />` shows the message. A JSON request gets a 422 response. The code below `validate()` does not run for a blocked submission.

### Why the `array_map()` line is there

A default Laravel application runs the `ConvertEmptyStringsToNull` middleware on every request. It turns the empty bait field into `null`. `validate()` treats a bait of `null` as "the field was not submitted" and rejects the form with "Spam detected.".

The `array_map()` call turns `null` back into an empty string before the honeypot looks at it. A bait field that is really missing stays missing and is still rejected. Your own `$request->validate()` call is not affected.

You can leave `array_map()` out only when your application has removed `ConvertEmptyStringsToNull` from its middleware.

## Where the error appears

The error is reported under the `field_name` key, `hp_website` by default. `<x-honeypot />` reads that key and shows the message in `<p class="hp-error" role="alert">`. Don't add your own `@error('hp_website')`, or the message appears twice.

## A different minimum time for one form

```php
$honeypot->validate($data, minimumSeconds: 10);
```

`$data` is the array from the example above. Without the second argument the `minimum_fill_seconds` setting applies.

## Expiry and key rotation

- A plain form expires after `maximum_fill_seconds`, one day by default. The token is part of the HTML, so without expiry a bot could copy it once and reuse it forever. A visitor who submits an expired form sees "This form has expired. Please try again." and gets a fresh token when the page reloads. Set `HONEYPOT_MAXIMUM_FILL_SECONDS=0` to turn expiry off.
- When you rotate `APP_KEY`, put the old key in `APP_PREVIOUS_KEYS` in `.env`. Laravel reads that variable into `app.previous_keys`, and the package accepts tokens signed with those keys. Forms that were open during the rotation then still validate.

## Page caching

The token holds the time the page was rendered. When a full page is cached, every visitor gets the same old token. Two things follow:

- The time check always passes, because the token looks older than `minimum_fill_seconds`. The bait field still works.
- Once the cached page is older than `maximum_fill_seconds`, every visitor gets "This form has expired. Please try again.".

Exclude pages with a form from the page cache, or build the form as a [Livewire component](livewire.md).

## A plain form inside a Livewire component

A form in a Livewire view that posts to a controller, such as a newsletter signup in a footer component, also gets the plain variant. `<x-honeypot />` switches to Livewire mode only when the component that renders it uses the `HasHoneypot` trait.

## Rendering the inputs yourself

When you can't use the Blade component, for example in an Inertia or JavaScript form, ask the service for the values:

```php
use Darvis\LivewireHoneypot\Services\HoneypotService;

$fields = app(HoneypotService::class)->generate();
// ['hp_website' => '', 'hp_started_at' => 1789657867, 'hp_token' => 'Xy3k….1789657867.9f2c…']
```

1. Pass `$fields` to your page.
2. Render the bait as a text input named `hp_website` (the `field_name` setting) and hide it with CSS. Leave its value empty.
3. Send `hp_token` back unchanged, together with the bait.
4. Validate in the controller exactly as in the example above.

`hp_started_at` is only there for backward compatibility. The service reads the start time from the signed token and ignores this field.

The default name `hp_website` contains "website", a word browser autofill recognises. When you render the input yourself, set `HONEYPOT_FIELD_NAME` to a word autofill does not know, such as `remarks_field`. Avoid "website", "url", "email", "name", "phone" and "company". [Your honeypot may be blocking real visitors](autofill-blocks-real-visitors.md) explains why.
