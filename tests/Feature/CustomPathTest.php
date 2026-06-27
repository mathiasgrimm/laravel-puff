<?php

use Illuminate\Support\Facades\Route;
use MathiasGrimm\Puff\Tests\CustomPathTestCase;

uses(CustomPathTestCase::class);

it('honours a custom path and name', function () {
    expect(Route::has('warm-up'))->toBeTrue();
    expect(Route::has('puff'))->toBeFalse();
    expect(Route::getRoutes()->getByName('warm-up')->uri())->toBe('warm-up');
});
