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
    | Warmers
    |--------------------------------------------------------------------------
    |
    | Backing services to touch on each request so a scale-to-zero stack is warm
    | before the user's real request arrives. Each touch is wrapped in its own
    | swallowed try/catch. On a cold start the connection attempt itself is what
    | wakes the service, so it must never turn into an error.
    |
    | 'database' is a list of connection names to ping (null = default
    | connection). 'cache' toggles a single cache read.
    |
    */

    'warm' => [
        'database' => [null],
        'cache' => true,
    ],

];
