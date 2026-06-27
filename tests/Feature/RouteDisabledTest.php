<?php

use Illuminate\Support\Facades\Route;
use MathiasGrimm\Puff\Tests\RouteDisabledTestCase;

uses(RouteDisabledTestCase::class);

it('does not register the route when register_route is false', function () {
    expect(Route::has('puff'))->toBeFalse();
});
