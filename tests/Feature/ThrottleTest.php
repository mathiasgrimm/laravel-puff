<?php

use Illuminate\Support\Facades\Route;
use MathiasGrimm\Puff\Tests\TestCase;

uses(TestCase::class);

it('applies the configured throttle middleware to the warm route', function () {
    $route = Route::getRoutes()->getByName('puff');

    expect($route->middleware())->toContain('throttle:60,1');
});
