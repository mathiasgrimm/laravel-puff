# laravel-puff

> Keep your scale-to-zero Laravel app warm.

On a scale-to-zero stack (Laravel Cloud, serverless, autosleeping containers) the
backing services spin down when idle, so the first real request after a quiet
period eats a cold-start penalty. **laravel-puff** fixes that: when a visitor
shows *intent* to act (moving the mouse, typing, scrolling, touching the screen,
or returning to the tab), the browser fires a lightweight, throttled `POST /puff`.
The endpoint touches your database and Redis to warm them *ahead of* the
visitor's real request.

Every activity signal funnels through the same throttle (one request per 30s by
default), so coverage is broad but the request rate never climbs. It runs on
every page, for every visitor. The endpoint is public by default (it only does a
`select 1` and a Redis PING), so guests warm the stack ahead of logging in too.

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

`puff:install` does two things:

1. Publishes the config and the keep-alive JS (the framework-agnostic core +
   a Vue composable) into `resources/js/laravel-puff/`.
2. Wires a global `startPuff()` call into your JS entry (`resources/js/app.ts`),
   so warming runs on every page out of the box.

Flags: `--no-wire` to skip step 2, `--entry=path/to/app.ts` to target a different
entry file, `--force` to overwrite published files, `--stack=vue` (default; other
stacks coming soon).

That's it. Move the mouse or switch back to the tab and you'll see a single
`POST /puff` → `204`, throttled to at most one per 30 seconds.

### Wiring it yourself

`startPuff()` is the framework-agnostic core (no Vue required). If you skipped
`--no-wire`, add it to your entry manually:

```ts
import { startPuff } from '@/laravel-puff/puff';

startPuff();
```

### Vue composable (opt-in)

Prefer per-component control with automatic cleanup on unmount? Use the Vue
composable in a layout's `<script setup>` instead:

```ts
import { usePuff } from '@/laravel-puff/usePuff';

usePuff();
```

Both accept the same options. To restrict warming (e.g. authenticated users
only), pass an `isEnabled` predicate:

```ts
import { usePage } from '@inertiajs/vue3';

startPuff({ isEnabled: () => !!usePage().props.auth?.user });
```

## Configuration

`config/puff.php`:

```php
return [
    'register_route' => true,          // set false to define the route yourself
    'path'           => 'puff',        // POST /puff
    'name'           => 'puff',        // route name
    'middleware'     => ['web'],       // public by default; add 'auth' to restrict
    'warm' => [
        'database' => [null],          // DB connection names to `select 1` (null = default; [] to skip)
        'redis'    => [null],          // Redis connection names to PING (null = default; [] to skip)
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
