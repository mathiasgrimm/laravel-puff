<?php

use Illuminate\Support\Facades\Route;
use MathiasGrimm\Puff\Tests\ThrottleDisabledTestCase;

uses(ThrottleDisabledTestCase::class);

it('does not apply throttle middleware when disabled', function () {
    $route = Route::getRoutes()->getByName('puff');

    expect($route->middleware())->toBe(['web']);
});
