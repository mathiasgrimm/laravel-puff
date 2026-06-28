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

`puff:install` does three things:

1. Publishes the config and the warm-up JS (the framework-agnostic core +
   a framework adapter) into `resources/js/laravel-puff/`.
2. Wires a global `startPuff()` call into your JS entry (`resources/js/app.ts`
   or `resources/js/app.tsx`), so warming runs on every page out of the box.
3. Adds `puff:publish` to your `composer.json` `post-update-cmd` (a one-line
   string edit that leaves the rest of the file untouched) so the stub re-syncs
   with the installed package version on every `composer update`. If you have no
   `post-update-cmd` yet, it prints the line to add instead of reformatting.

The stack is auto-detected (Vue or React) from your entry file and
`package.json`, so the Vue and React starter kits both work with a bare
`php artisan puff:install`. If it can't tell, the command stops and asks you to
pass `--stack=vue` or `--stack=react` rather than guessing.

Flags: `--no-wire` to skip step 2, `--no-scripts` to skip step 3,
`--entry=path/to/app.tsx` to target a different entry file, `--force` to
overwrite published files, `--stack=vue|react` to override auto-detection.

That's it. Move the mouse or switch back to the tab and you'll see a single
`POST /puff` → `204`, throttled to at most one per 30 seconds.

### Keeping the stub up to date

The published JS (`resources/js/laravel-puff/`) is a copy, so `composer update`
alone won't refresh it. Treat that folder as package-owned (don't edit it).
`puff:install` already adds the following to your `composer.json`, so every
update re-publishes the stub from the installed version:

```json
"scripts": {
    "post-update-cmd": [
        "@php artisan puff:publish --ansi"
    ]
}
```

If you installed with `--no-scripts`, add it yourself. `puff:publish`
force-republishes the core + the adapter for whichever stack you installed (it
never touches `config/puff.php`, which stays yours). You can also run it by hand
any time: `php artisan puff:publish`.

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

To restrict warming (e.g. authenticated users only), pass an `isEnabled`
predicate:

```ts
import { usePage } from '@inertiajs/vue3';

usePuff({ isEnabled: () => !!usePage().props.auth?.user });
```

### React hook (opt-in)

On React (e.g. the React starter kit), the published `usePuff` is a hook that
starts warming on mount and cleans up on unmount. Call it once in a layout:

```tsx
import { usePuff } from '@/laravel-puff/usePuff';

usePuff();
```

To restrict warming (e.g. authenticated users only), pass an `isEnabled`
predicate:

```tsx
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
    'throttle'       => '60,1',         // rate limit (maxAttempts,decayMinutes); null to disable
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
