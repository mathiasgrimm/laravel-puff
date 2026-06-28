<?php

namespace MathiasGrimm\Puff\Console;

use Illuminate\Console\Command;
use MathiasGrimm\Puff\Console\Concerns\InteractsWithStacks;

class PublishCommand extends Command
{
    use InteractsWithStacks;

    /**
     * @var string
     */
    protected $signature = 'puff:publish';

    /**
     * @var string
     */
    protected $description = 'Re-publish the Puff frontend stub so it tracks the installed package version';

    /**
     * Force-republish the already-installed stub (core + adapter). Meant for an
     * app's composer `post-update-cmd`, so `composer update` keeps the published
     * files in sync with vendor, the same way Telescope/Horizon/Nova do for
     * their assets. The config is never touched; it stays user-owned.
     */
    public function handle(): int
    {
        $stack = $this->installedStack();

        if ($stack === null) {
            // Not installed yet: nothing to sync. Stay successful so this never
            // breaks a `composer update` on an app that hasn't run puff:install.
            $this->components->info('Puff is not installed yet. Run `php artisan puff:install` first.');

            return self::SUCCESS;
        }

        $this->call('vendor:publish', [
            '--tag' => self::STACK_TAGS[$stack],
            '--force' => true,
        ]);

        $this->components->info("Re-published the Puff [{$stack}] stub.");

        return self::SUCCESS;
    }
}
