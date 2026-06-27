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
        {--entry= : Path (relative to the app root) of the JS entry file to wire startPuff() into}
        {--no-wire : Publish the stub but do not modify the entry file}
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
                "The [{$stack}] stack is coming soon. Only [vue] is available right now. "
                .'The framework-agnostic core ships today so other adapters are additive.'
            );

            return self::SUCCESS;
        }

        $this->call('vendor:publish', [
            '--tag' => 'puff-vue',
            '--force' => $force,
        ]);

        if (! $this->option('no-wire')) {
            $this->wireStartPuff();
        }

        $this->components->info(
            'Puff installed. Move the mouse or switch tabs and you should see a throttled '
            .'POST /puff on any page.'
        );

        return self::SUCCESS;
    }

    /**
     * Best-effort: drop a global `startPuff()` call into the app's JS entry file
     * (e.g. resources/js/app.ts) so the stack is warmed on every page, for every
     * visitor. Never corrupts a file: if it can't find a safe spot, it prints
     * manual instructions and moves on.
     */
    private function wireStartPuff(): void
    {
        $entry = $this->resolveEntry();

        if ($entry === null) {
            $this->components->warn(
                'Could not find a JS entry file. Import startPuff from '
                ."'@/laravel-puff/puff' and call startPuff() in your app entry manually (see the README)."
            );

            return;
        }

        $content = (string) file_get_contents($entry);
        $relative = $this->relativePath($entry);

        if (preg_match('/\bstartPuff\s*\(/', $content) === 1) {
            $this->components->info("startPuff() is already wired into {$relative}.");

            return;
        }

        $import = "import { startPuff } from '@/laravel-puff/puff';";

        // Place the import after the last existing import, else at the top.
        if (preg_match_all('/^\s*import\b.*?;\s*$/m', $content, $imports, PREG_OFFSET_CAPTURE) >= 1) {
            $last = end($imports[0]);
            $insertAt = $last[1] + strlen($last[0]);
            $content = substr_replace($content, "\n".$import, $insertAt, 0);
        } else {
            $content = $import."\n".$content;
        }

        // Call it at module scope, after the app has been created.
        $content = rtrim($content, "\n")
            ."\n\n// Warm the scale-to-zero stack whenever a visitor is active.\nstartPuff();\n";

        file_put_contents($entry, $content);

        $this->components->info("Wired startPuff() into {$relative}.");
    }

    /**
     * Pick the JS entry to edit: an explicit --entry, then the conventional
     * resources/js/app.ts / app.js.
     */
    private function resolveEntry(): ?string
    {
        if ($override = $this->option('entry')) {
            $path = base_path((string) $override);

            return is_file($path) ? $path : null;
        }

        foreach (['js/app.ts', 'js/app.js'] as $candidate) {
            $path = resource_path($candidate);

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function relativePath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
