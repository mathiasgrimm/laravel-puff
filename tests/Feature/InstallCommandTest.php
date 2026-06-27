<?php

use MathiasGrimm\Puff\Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    @unlink(config_path('puff.php'));
    @unlink(resource_path('js/laravel-puff/puff.ts'));
    @unlink(resource_path('js/laravel-puff/usePuff.ts'));
    @rmdir(resource_path('js/laravel-puff'));
    @unlink(resource_path('js/app.ts'));
});

it('publishes the config and the vue stub', function () {
    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--force' => true])
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

it('wires startPuff into the js entry file', function () {
    file_put_contents(
        resource_path('js/app.ts'),
        "import { createInertiaApp } from '@inertiajs/vue3';\n\ncreateInertiaApp({});\n"
    );

    $this->artisan('puff:install', ['--stack' => 'vue', '--force' => true])
        ->assertSuccessful();

    $content = file_get_contents(resource_path('js/app.ts'));

    expect($content)->toContain("import { startPuff } from '@/laravel-puff/puff';")
        ->and($content)->toContain('startPuff();');
});

it('does not wire startPuff twice', function () {
    file_put_contents(
        resource_path('js/app.ts'),
        "import { createInertiaApp } from '@inertiajs/vue3';\n\ncreateInertiaApp({});\n"
    );

    $this->artisan('puff:install', ['--stack' => 'vue', '--force' => true])->assertSuccessful();
    $this->artisan('puff:install', ['--stack' => 'vue', '--force' => true])->assertSuccessful();

    $content = file_get_contents(resource_path('js/app.ts'));

    expect(substr_count($content, 'startPuff();'))->toBe(1);
});

it('skips wiring when --no-wire is passed', function () {
    file_put_contents(
        resource_path('js/app.ts'),
        "import { createInertiaApp } from '@inertiajs/vue3';\n\ncreateInertiaApp({});\n"
    );

    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents(resource_path('js/app.ts')))->not->toContain('startPuff');
});
