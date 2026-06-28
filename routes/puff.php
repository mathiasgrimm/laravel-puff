<?php

use Illuminate\Support\Facades\Route;
use MathiasGrimm\Puff\Http\Controllers\PuffController;

$middleware = (array) config('puff.middleware', ['web']);

if ($throttle = config('puff.throttle')) {
    $middleware[] = 'throttle:'.$throttle;
}

Route::post(config('puff.path', 'puff'), PuffController::class)
    ->middleware($middleware)
    ->name(config('puff.name', 'puff'));
