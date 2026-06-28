<?php

namespace MathiasGrimm\Puff\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'puff:install
        {--stack= : The frontend stack to install the keep-alive stub for (vue|react); auto-detected when omitted}
        {--entry= : Path (relative to the app root) of the JS entry file to wire startPuff() into}
        {--no-wire : Publish the stub but do not modify the entry file}
        {--force : Overwrite any existing published files}';

    /**
     * Supported frontend stacks mapped to the publish tag that ships their
     * adapter (each tag publishes the shared core + that stack's usePuff).
     *
     * @var array<string, string>
     */
    private const STACK_TAGS = [
        'vue' => 'puff-vue',
        'react' => 'puff-react',
    ];

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

        $stack = $this->resolveStack();

        if (! isset(self::STACK_TAGS[$stack])) {
            $supported = implode(', ', array_keys(self::STACK_TAGS));

            $this->components->warn(
                "The [{$stack}] stack is not supported. Supported stacks: {$supported}. "
                .'The framework-agnostic core ships today so other adapters are additive.'
            );

            return self::SUCCESS;
        }

        $this->components->info("Installing the [{$stack}] keep-alive stub.");

        $this->call('vendor:publish', [
            '--tag' => self::STACK_TAGS[$stack],
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
     * Resolve the stack to install: an explicit --stack always wins, otherwise
     * auto-detect from the app so React Starter Kit users can just run
     * `puff:install`. Falls back to `vue`.
     */
    private function resolveStack(): string
    {
        if ($override = $this->option('stack')) {
            return strtolower((string) $override);
        }

        return $this->detectStack();
    }

    /**
     * Sniff the app for its frontend stack. The entry file extension is the
     * strongest signal (app.tsx/app.jsx => react), then package.json
     * dependencies. Defaults to `vue` when nothing is conclusive.
     */
    private function detectStack(): string
    {
        $entry = $this->resolveEntry();

        if ($entry !== null && preg_match('/\.(tsx|jsx)$/', $entry) === 1) {
            return 'react';
        }

        $packageJson = base_path('package.json');

        if (is_file($packageJson)) {
            $manifest = json_decode((string) file_get_contents($packageJson), true);

            $deps = array_merge(
                (array) ($manifest['dependencies'] ?? []),
                (array) ($manifest['devDependencies'] ?? []),
            );

            if (isset($deps['react']) && ! isset($deps['vue'])) {
                return 'react';
            }
        }

        return 'vue';
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

        foreach (['js/app.ts', 'js/app.tsx', 'js/app.js', 'js/app.jsx'] as $candidate) {
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
