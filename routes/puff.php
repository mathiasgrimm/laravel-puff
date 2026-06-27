<?php

use Illuminate\Support\Facades\Route;
use MathiasGrimm\Puff\Http\Controllers\PuffController;

Route::post(config('puff.path', 'puff'), PuffController::class)
    ->middleware(config('puff.middleware', ['web']))
    ->name(config('puff.name', 'puff'));
