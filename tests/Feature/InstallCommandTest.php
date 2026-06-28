<?php

use MathiasGrimm\Puff\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->composerPath = base_path('composer.json');
    $this->composerBackup = is_file($this->composerPath)
        ? file_get_contents($this->composerPath)
        : null;
});

afterEach(function () {
    if ($this->composerBackup !== null) {
        file_put_contents($this->composerPath, $this->composerBackup);
    }

    @unlink(config_path('puff.php'));
    @unlink(resource_path('js/laravel-puff/puff.ts'));
    @unlink(resource_path('js/laravel-puff/usePuff.ts'));
    @rmdir(resource_path('js/laravel-puff'));
    @unlink(resource_path('js/app.ts'));
    @unlink(resource_path('js/app.tsx'));
    @unlink(base_path('package.json'));
});

it('publishes the config and the vue stub', function () {
    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--force' => true])
        ->assertSuccessful();

    expect(file_exists(config_path('puff.php')))->toBeTrue()
        ->and(file_exists(resource_path('js/laravel-puff/puff.ts')))->toBeTrue()
        ->and(file_exists(resource_path('js/laravel-puff/usePuff.ts')))->toBeTrue();
});

it('publishes the config and the react stub', function () {
    $this->artisan('puff:install', ['--stack' => 'react', '--no-wire' => true, '--force' => true])
        ->assertSuccessful();

    expect(file_exists(config_path('puff.php')))->toBeTrue()
        ->and(file_exists(resource_path('js/laravel-puff/puff.ts')))->toBeTrue()
        ->and(file_exists(resource_path('js/laravel-puff/usePuff.ts')))->toBeTrue()
        ->and(file_get_contents(resource_path('js/laravel-puff/usePuff.ts')))
        ->toContain("from 'react'");
});

it('fails for an unsupported stack', function () {
    $this->artisan('puff:install', ['--stack' => 'svelte'])
        ->assertFailed();

    expect(file_exists(resource_path('js/laravel-puff/usePuff.ts')))->toBeFalse();
});

it('fails when the stack cannot be detected', function () {
    $this->artisan('puff:install')
        ->assertFailed();

    expect(file_exists(resource_path('js/laravel-puff/usePuff.ts')))->toBeFalse();
});

it('wires startPuff into a react .tsx entry file', function () {
    file_put_contents(resource_path('js/app.tsx'), <<<'TS'
        import { createInertiaApp } from '@inertiajs/react';

        createInertiaApp({});
        TS);

    $this->artisan('puff:install', ['--stack' => 'react', '--force' => true])
        ->assertSuccessful();

    $content = file_get_contents(resource_path('js/app.tsx'));

    expect($content)->toContain("import { startPuff } from '@/laravel-puff/puff';")
        ->and($content)->toContain('startPuff();');
});

it('auto-detects the react stack from a .tsx entry', function () {
    file_put_contents(resource_path('js/app.tsx'), <<<'TS'
        import { createInertiaApp } from '@inertiajs/react';

        createInertiaApp({});
        TS);

    $this->artisan('puff:install', ['--force' => true])
        ->assertSuccessful();

    expect(file_get_contents(resource_path('js/laravel-puff/usePuff.ts')))
        ->toContain("from 'react'")
        ->and(file_get_contents(resource_path('js/app.tsx')))
        ->toContain('startPuff();');
});

it('auto-detects vue from package.json when the entry is .ts', function () {
    file_put_contents(resource_path('js/app.ts'), "createInertiaApp({});\n");
    file_put_contents(base_path('package.json'), json_encode(['dependencies' => ['vue' => '^3.0']]));

    $this->artisan('puff:install', ['--no-wire' => true, '--no-scripts' => true, '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents(resource_path('js/laravel-puff/usePuff.ts')))->toContain("from 'vue'");
});

it('does not default to vue: fails on a .ts entry when package.json is inconclusive', function () {
    file_put_contents(resource_path('js/app.ts'), "createInertiaApp({});\n");

    $this->artisan('puff:install', ['--no-wire' => true, '--no-scripts' => true])
        ->assertFailed();

    expect(file_exists(resource_path('js/laravel-puff/usePuff.ts')))->toBeFalse();
});

it('wires startPuff into the js entry file', function () {
    file_put_contents(resource_path('js/app.ts'), <<<'TS'
        import { createInertiaApp } from '@inertiajs/vue3';

        createInertiaApp({});
        TS);

    $this->artisan('puff:install', ['--stack' => 'vue', '--force' => true])
        ->assertSuccessful();

    $content = file_get_contents(resource_path('js/app.ts'));

    expect($content)->toContain("import { startPuff } from '@/laravel-puff/puff';")
        ->and($content)->toContain('startPuff();');
});

it('does not wire startPuff twice', function () {
    file_put_contents(resource_path('js/app.ts'), <<<'TS'
        import { createInertiaApp } from '@inertiajs/vue3';

        createInertiaApp({});
        TS);

    $this->artisan('puff:install', ['--stack' => 'vue', '--force' => true])->assertSuccessful();
    $this->artisan('puff:install', ['--stack' => 'vue', '--force' => true])->assertSuccessful();

    $content = file_get_contents(resource_path('js/app.ts'));

    expect(substr_count($content, 'startPuff();'))->toBe(1);
});

it('skips wiring when --no-wire is passed', function () {
    file_put_contents(resource_path('js/app.ts'), <<<'TS'
        import { createInertiaApp } from '@inertiajs/vue3';

        createInertiaApp({});
        TS);

    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents(resource_path('js/app.ts')))->not->toContain('startPuff');
});

it('warns when files already exist and --force is not used', function () {
    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--no-scripts' => true, '--force' => true])
        ->assertSuccessful();

    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--no-scripts' => true])
        ->expectsOutputToContain('already exist')
        ->assertSuccessful();
});

it('adds puff:publish to an existing post-update-cmd', function () {
    file_put_contents(base_path('composer.json'), <<<'JSON'
        {
            "scripts": {
                "post-update-cmd": [
                    "@php artisan package:discover --ansi"
                ]
            }
        }
        JSON);

    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--force' => true])
        ->assertSuccessful();

    $cmds = json_decode((string) file_get_contents(base_path('composer.json')), true)['scripts']['post-update-cmd'];

    expect($cmds)->toContain('@php artisan package:discover --ansi')
        ->and($cmds)->toContain('@php artisan puff:publish --ansi');
});

it('inserts the script without reformatting the rest of composer.json', function () {
    // 2-space top level, 4-space scripts, 6-space entries, plus an empty object.
    $original = <<<'JSON'
        {
          "name": "acme/app",
          "extra": {},
          "scripts": {
            "post-update-cmd": [
              "@php artisan package:discover --ansi"
            ]
          }
        }
        JSON;
    file_put_contents(base_path('composer.json'), $original);

    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--force' => true])
        ->assertSuccessful();

    $updated = (string) file_get_contents(base_path('composer.json'));

    expect($updated)
        ->toContain('  "name": "acme/app",')          // untouched 2-space indent
        ->toContain('  "extra": {},')                    // empty object preserved, not turned into []
        ->toContain('      "@php artisan package:discover --ansi"') // sibling kept verbatim
        ->toContain('      "@php artisan puff:publish --ansi",');   // new line matches the 6-space indent
});

it('does not duplicate the composer script when run twice', function () {
    file_put_contents(base_path('composer.json'), <<<'JSON'
        {
            "scripts": {
                "post-update-cmd": [
                    "@php artisan package:discover --ansi"
                ]
            }
        }
        JSON);

    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--force' => true])->assertSuccessful();
    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--force' => true])->assertSuccessful();

    $cmds = json_decode((string) file_get_contents(base_path('composer.json')), true)['scripts']['post-update-cmd'];

    expect(count(array_keys($cmds, '@php artisan puff:publish --ansi', true)))->toBe(1);
});

it('leaves composer.json unchanged when there is no post-update-cmd', function () {
    $original = <<<'JSON'
        {
            "name": "acme/app"
        }
        JSON;
    file_put_contents(base_path('composer.json'), $original);

    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents(base_path('composer.json')))->toBe($original);
});

it('leaves composer.json untouched with --no-scripts', function () {
    $original = <<<'JSON'
        {
            "scripts": {
                "post-update-cmd": [
                    "@php artisan package:discover --ansi"
                ]
            }
        }
        JSON;
    file_put_contents(base_path('composer.json'), $original);

    $this->artisan('puff:install', ['--stack' => 'vue', '--no-wire' => true, '--no-scripts' => true, '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents(base_path('composer.json')))->toBe($original);
});
