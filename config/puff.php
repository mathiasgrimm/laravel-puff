<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route registration
    |--------------------------------------------------------------------------
    |
    | When true, the package registers its own POST route. Set to false if you
    | prefer to define the route yourself and point it at the controller.
    |
    */

    'register_route' => true,

    'path' => 'puff',

    'name' => 'puff',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Public by default so the stack is warmed for everyone (guests included).
    | The endpoint only runs a `select 1` and a cache read, then returns 204.
    | Add 'auth' here if you only want logged-in users to be able to warm it.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Throttle
    |--------------------------------------------------------------------------
    |
    | The endpoint is public and touches your backing services on every hit, so
    | a rate limit keeps it from being abused as a cheap way to hammer the DB and
    | Redis. The value is Laravel's throttle string, "maxAttempts,decayMinutes",
    | applied per client. Warming is best-effort and the browser ignores failed
    | requests, so a throttled (429) warm is harmless. Set to null to disable.
    |
    */

    'throttle' => '60,1',

    /*
    |--------------------------------------------------------------------------
    | Warmers
    |--------------------------------------------------------------------------
    |
    | Backing services to touch on each request so a scale-to-zero stack is warm
    | before the user's real request arrives. Each touch is wrapped in its own
    | swallowed try/catch. On a cold start the connection attempt itself is what
    | wakes the service, so it must never turn into an error.
    |
    | Each warmer has an 'enabled' switch and a 'connections' list. 'database'
    | runs `select 1` on every listed connection; 'redis' runs a PING. An empty
    | 'connections' list falls back to the default connection. Both DB and Redis
    | can have several connections, so list any you want to keep warm.
    |
    */

    'warm' => [
        'database' => [
            'enabled' => true,
            'connections' => [],
        ],
        'redis' => [
            'enabled' => true,
            'connections' => [],
        ],
    ],

];
