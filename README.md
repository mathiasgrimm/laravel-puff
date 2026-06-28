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
   a framework adapter) into `resources/js/laravel-puff/`.
2. Wires a global `startPuff()` call into your JS entry (`resources/js/app.ts`
   or `resources/js/app.tsx`), so warming runs on every page out of the box.

The stack is auto-detected (Vue or React) from your entry file and
`package.json`, so the Vue and React starter kits both work with a bare
`php artisan puff:install`. If it can't tell, the command stops and asks you to
pass `--stack=vue` or `--stack=react` rather than guessing.

Flags: `--no-wire` to skip step 2, `--entry=path/to/app.tsx` to target a
different entry file, `--force` to overwrite published files, `--stack=vue|react`
to override auto-detection.

That's it. Move the mouse or switch back to the tab and you'll see a single
`POST /puff` → `204`, throttled to at most one per 30 seconds.

### Keeping the stub up to date

The published JS (`resources/js/laravel-puff/`) is a copy, so `composer update`
alone won't refresh it. Treat that folder as package-owned (don't edit it) and,
like Telescope/Horizon/Nova do for their assets, add `puff:publish` to your
app's `composer.json` so every update re-syncs it with the installed version:

```json
"scripts": {
    "post-update-cmd": [
        "@php artisan puff:publish --ansi"
    ]
}
```

`puff:publish` force-republishes the core + the adapter for whichever stack you
installed (it never touches `config/puff.php`, which stays yours). You can also
run it by hand any time: `php artisan puff:publish`.

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

### React hook (opt-in)

On React (e.g. the React starter kit), the published `usePuff` is a hook that
starts warming on mount and cleans up on unmount. Call it once in a layout:

```tsx
import { usePuff } from '@/laravel-puff/usePuff';

usePuff();
```

Both adapters accept the same options. To restrict warming (e.g. authenticated
users only), pass an `isEnabled` predicate:

```ts
// Vue
import { usePage } from '@inertiajs/vue3';

startPuff({ isEnabled: () => !!usePage().props.auth?.user });
```

```tsx
// React
import { usePage } from '@inertiajs/react';

usePuff({ isEnabled: () => !!usePage().props.auth?.user });
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
