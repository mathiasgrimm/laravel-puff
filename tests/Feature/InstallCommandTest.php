<?php

use MathiasGrimm\Puff\Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    @unlink(config_path('puff.php'));
    @unlink(resource_path('js/laravel-puff/puff.ts'));
    @unlink(resource_path('js/laravel-puff/usePuff.ts'));
    @rmdir(resource_path('js/laravel-puff'));
});

it('publishes the config and the vue stub', function () {
    $this->artisan('puff:install', ['--stack' => 'vue', '--force' => true])
        ->assertSuccessful();

    expect(file_exists(config_path('puff.php')))->toBeTrue()
        ->and(file_exists(resource_path('js/laravel-puff/puff.ts')))->toBeTrue()
        ->and(file_exists(resource_path('js/laravel-puff/usePuff.ts')))->toBeTrue();
});

it('does not publish a frontend stub for an unimplemented stack', function () {
    $this->artisan('puff:install', ['--stack' => 'react'])
        ->assertSuccessful();

    expect(file_exists(resource_path('js/laravel-puff/usePuff.ts')))->toBeFalse();
});
