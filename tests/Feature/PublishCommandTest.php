<?php

use MathiasGrimm\Puff\Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    @unlink(config_path('puff.php'));
    @unlink(resource_path('js/laravel-puff/puff.ts'));
    @unlink(resource_path('js/laravel-puff/usePuff.ts'));
    @rmdir(resource_path('js/laravel-puff'));
});

it('succeeds without publishing when puff is not installed', function () {
    $this->artisan('puff:publish')->assertSuccessful();

    expect(file_exists(resource_path('js/laravel-puff/usePuff.ts')))->toBeFalse();
});

it('re-publishes the installed stub, overwriting local drift', function () {
    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--no-scripts' => true, '--force' => true])
        ->assertSuccessful();

    file_put_contents(resource_path('js/laravel-puff/puff.ts'), 'stale');

    $this->artisan('puff:publish')->assertSuccessful();

    expect(file_get_contents(resource_path('js/laravel-puff/puff.ts')))->not->toBe('stale');
});

it('re-publishes the react adapter when react is installed', function () {
    $this->artisan('puff:install', ['--stack' => 'react', '--no-wire' => true, '--no-scripts' => true, '--force' => true])
        ->assertSuccessful();

    file_put_contents(resource_path('js/laravel-puff/usePuff.ts'), "import { useEffect } from 'react';");

    $this->artisan('puff:publish')->assertSuccessful();

    expect(file_get_contents(resource_path('js/laravel-puff/usePuff.ts')))
        ->toContain('usePuff')
        ->and(file_get_contents(resource_path('js/laravel-puff/usePuff.ts')))
        ->toContain("from 'react'");
});

it('does not publish the config', function () {
    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--no-scripts' => true, '--force' => true])
        ->assertSuccessful();

    @unlink(config_path('puff.php'));

    $this->artisan('puff:publish')->assertSuccessful();

    expect(file_exists(config_path('puff.php')))->toBeFalse();
});
