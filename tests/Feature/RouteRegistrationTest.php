<?php

use Illuminate\Support\Facades\Route;
use MathiasGrimm\Puff\Tests\TestCase;

uses(TestCase::class);

it('registers the puff route by default', function () {
    expect(Route::has('puff'))->toBeTrue();

    $route = Route::getRoutes()->getByName('puff');

    expect($route->uri())->toBe('puff');
    expect($route->methods())->toContain('POST');
});
