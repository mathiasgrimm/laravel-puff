<?php

namespace MathiasGrimm\Puff\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'puff:install
        {--stack=vue : The frontend stack to install the keep-alive stub for (vue)}
        {--force : Overwrite any existing published files}';

    /**
     * @var string
     */
    protected $description = 'Publish the Puff config and the frontend keep-alive stub';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->call('vendor:publish', [
            '--tag' => 'puff-config',
            '--force' => $force,
        ]);

        $stack = (string) $this->option('stack');

        if ($stack !== 'vue') {
            $this->components->warn(
                "The [{$stack}] stack is coming soon. Only [vue] is available right now — "
                .'the framework-agnostic core ships today so other adapters are additive.'
            );

            return self::SUCCESS;
        }

        $this->call('vendor:publish', [
            '--tag' => 'puff-vue',
            '--force' => $force,
        ]);

        $this->components->info(
            'Puff installed. Import usePuff() from resources/js/laravel-puff/usePuff.ts '
            .'in your root layout to start warming the stack.'
        );

        return self::SUCCESS;
    }
}
