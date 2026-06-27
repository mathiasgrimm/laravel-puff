# laravel-puff

> Keep your scale-to-zero Laravel app warm.

On a scale-to-zero stack (Laravel Cloud, serverless, autosleeping containers) the
backing services spin down when idle, so the first real request after a quiet
period eats a cold-start penalty. **laravel-puff** fixes that: when a logged-in
user shows *intent* to act — moving the mouse, typing, scrolling, touching the
screen, or returning to the tab — the browser fires a lightweight, throttled
`POST /puff`. The endpoint touches your database and cache to warm them *ahead of*
the user's real request.

Every activity signal funnels through the same throttle (one request per 30s by
default), so coverage is broad but the request rate never climbs. Guests do
nothing.

- Tiny, framework-agnostic JS core (no axios, no Wayfinder, no Inertia coupling).
- Configurable endpoint, middleware, and warm targets.
- Ships a Vue adapter today; the core is framework-agnostic so React, Svelte, and
  Livewire/Blade adapters are additive.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

```bash
composer require mathiasgrimm/laravel-puff
php artisan puff:install
```

`puff:install` publishes the config and the Vue keep-alive stub into
`resources/js/laravel-puff/`. (Pass `--force` to overwrite, `--stack=vue` to be
explicit. Other stacks are coming soon.)

## Usage (Vue + Inertia)

Call the composable once in your authenticated root layout's `<script setup>`:

```ts
import { usePuff } from '@/laravel-puff/usePuff';
import { usePage } from '@inertiajs/vue3';

// Only warm for authenticated users.
usePuff({ isEnabled: () => !!usePage().props.auth?.user });
```

That's it. Move the mouse or switch back to the tab and you'll see a single
`POST /puff` → `204`, throttled to at most one per 30 seconds.

### Without Inertia

`isEnabled` is just a predicate — pass whatever tells you the user is logged in,
or omit it to always warm:

```ts
usePuff(); // always-on
```

## Configuration

`config/puff.php`:

```php
return [
    'register_route' => true,          // set false to define the route yourself
    'path'           => 'puff',        // POST /puff
    'name'           => 'puff',        // route name
    'middleware'     => ['web', 'auth'],
    'warm' => [
        'database' => [null],          // connection names to `select 1` (null = default)
        'cache'    => true,            // perform one Cache::get()
    ],
];
```

CSRF works out of the box: the core reads Laravel's `XSRF-TOKEN` cookie and sends
it as the `X-XSRF-TOKEN` header, so no meta tag or extra setup is needed.

## Frontend options

`usePuff(options)` / `startPuff(options)` accept:

| Option       | Default                                          | Description                                  |
| ------------ | ------------------------------------------------ | -------------------------------------------- |
| `url`        | `'/puff'`                                         | Endpoint to POST to                          |
| `intervalMs` | `30000`                                           | Minimum gap between requests                 |
| `events`     | `['mousemove','keydown','scroll','touchstart']`   | Activity events that trigger a warm          |
| `method`     | `'POST'`                                           | HTTP method                                  |
| `isEnabled`  | always-on                                          | Return `false` to skip (e.g. for guests)     |

The framework-agnostic core (`resources/js/laravel-puff/puff.ts`) exports
`startPuff(options): () => void` and returns a `stop()` cleanup, if you want to
wire it up yourself in another framework.

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE.md](LICENSE.md).
