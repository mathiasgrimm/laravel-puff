# laravel-puff

> Keep your scale-to-zero Laravel app warm.

Laravel Cloud's scale-to-zero is spectacular: it parks your environment when idle
and wakes it in ~500ms. **laravel-puff** makes it even better. When a visitor
shows *intent* to act (moving the mouse, typing, scrolling, or returning to the
tab), the browser fires a lightweight, throttled `POST /puff` that warms your
database and Redis *ahead of* the real request, so the cold start is already paid
for by the time they click.

It is **not** a poller. A heartbeat that pings on a timer would keep the
environment awake forever and defeat scale-to-zero. laravel-puff only fires on
genuine human activity, so an idle app still sleeps (and stops billing) the
moment everyone leaves.

- Tiny, framework-agnostic JS core (no axios, no Wayfinder, no Inertia coupling).
- Throttled (one request per 30s by default), public by default, runs everywhere.
- Ships a Vue adapter today; React, Svelte, and Livewire/Blade are additive.

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
        'database' => [
            'enabled'     => true,
            'connections' => [],       // empty = default; or ['mysql', 'reports']
        ],
        'redis' => [
            'enabled'     => true,
            'connections' => [],       // empty = default; or ['default', 'cache']
        ],
    ],
];
```

CSRF works out of the box: the core reads Laravel's `XSRF-TOKEN` cookie and sends
it as the `X-XSRF-TOKEN` header, so no meta tag or extra setup is needed.

## Frontend options

`usePuff(options)` / `startPuff(options)` accept:

| Option          | Default                                          | Description                                                     |
| --------------- | ------------------------------------------------ | -------------------------------------------------------------- |
| `url`           | `'/puff'`                                         | Endpoint to POST to                                            |
| `intervalMs`    | `30000`                                           | Minimum gap between requests                                  |
| `events`        | `['mousemove','keydown','scroll','touchstart']`   | Activity events (on `window`) that trigger a warm             |
| `method`        | `'POST'`                                           | HTTP method                                                   |
| `warmOnVisible` | `true`                                            | Also warm when the user returns to the tab (`visibilitychange`) |
| `isEnabled`     | always-on                                          | Return `false` to skip (e.g. for guests)                     |

The framework-agnostic core (`resources/js/laravel-puff/puff.ts`) exports
`startPuff(options): () => void` and returns a `stop()` cleanup, if you want to
wire it up yourself in another framework.

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE.md](LICENSE.md).
