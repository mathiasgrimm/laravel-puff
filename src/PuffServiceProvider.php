<?php

namespace MathiasGrimm\Puff;

use Illuminate\Support\ServiceProvider;
use MathiasGrimm\Puff\Console\InstallCommand;

class PuffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/puff.php', 'puff');
    }

    public function boot(): void
    {
        if (config('puff.register_route', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/puff.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);

            $this->publishes([
                __DIR__.'/../config/puff.php' => config_path('puff.php'),
            ], 'puff-config');

            // Publish the framework-agnostic core + a framework adapter side by
            // side into a dedicated, app-owned folder. The adapter imports the
            // core via a relative path, so each published pair is self-contained.
            // Both adapters publish to the same `usePuff.ts` name, so the wiring
            // and imports are identical no matter which stack is installed.
            $this->publishes([
                __DIR__.'/../resources/js/puff.ts' => resource_path('js/laravel-puff/puff.ts'),
                __DIR__.'/../resources/js/usePuff.ts' => resource_path('js/laravel-puff/usePuff.ts'),
            ], 'puff-vue');

            $this->publishes([
                __DIR__.'/../resources/js/puff.ts' => resource_path('js/laravel-puff/puff.ts'),
                __DIR__.'/../resources/js/usePuff.react.ts' => resource_path('js/laravel-puff/usePuff.ts'),
            ], 'puff-react');
        }
    }
}
