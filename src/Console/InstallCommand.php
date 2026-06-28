<?php

namespace MathiasGrimm\Puff\Console;

use Illuminate\Console\Command;
use MathiasGrimm\Puff\Console\Concerns\InteractsWithStacks;

class InstallCommand extends Command
{
    use InteractsWithStacks;

    /**
     * @var string
     */
    protected $signature = 'puff:install
        {--stack= : The frontend stack to install the warm-up stub for (vue|react); auto-detected when omitted}
        {--entry= : Path (relative to the app root) of the JS entry file to wire startPuff() into}
        {--no-wire : Publish the stub but do not modify the entry file}
        {--no-scripts : Do not add puff:publish to the app composer post-update-cmd}
        {--force : Overwrite any existing published files}';

    /**
     * @var string
     */
    protected $description = 'Publish the Puff config and the frontend warm-up stub';

    public function handle(): int
    {
        $supported = implode(', ', array_keys(self::STACK_TAGS));
        $stack = $this->resolveStack();

        if ($stack === null) {
            $this->components->error(
                'Could not detect your frontend stack. Re-run with an explicit '
                ."--stack option (supported: {$supported})."
            );

            return self::FAILURE;
        }

        if (! isset(self::STACK_TAGS[$stack])) {
            $this->components->error(
                "The [{$stack}] stack is not supported. Supported stacks: {$supported}."
            );

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        $existing = array_filter([
            config_path('puff.php'),
            resource_path('js/laravel-puff/puff.ts'),
            resource_path('js/laravel-puff/usePuff.ts'),
        ], 'is_file');

        if ($existing !== [] && ! $force) {
            $this->components->warn(
                'Some Puff files already exist and were left untouched. '
                .'Re-run with --force to overwrite them.'
            );
        }

        $this->call('vendor:publish', [
            '--tag' => 'puff-config',
            '--force' => $force,
        ]);

        $this->components->info("Installing the [{$stack}] warm-up stub.");

        $this->call('vendor:publish', [
            '--tag' => self::STACK_TAGS[$stack],
            '--force' => $force,
        ]);

        if (! $this->option('no-wire')) {
            $this->wireStartPuff();
        }

        if (! $this->option('no-scripts')) {
            $this->registerComposerScript();
        }

        $this->components->info(
            'Puff installed. Move the mouse or switch tabs and you should see a throttled '
            .'POST /puff on any page.'
        );

        return self::SUCCESS;
    }

    /**
     * Best-effort: add `puff:publish` to the app's composer `post-update-cmd`
     * so the published stub is re-synced on every `composer update`, the way
     * Telescope/Horizon/Nova keep their assets current.
     *
     * Inserts a single line into the existing post-update-cmd array via a string
     * edit, so the rest of composer.json keeps its exact formatting. When there
     * is no post-update-cmd array to extend, it prints the line to add by hand
     * rather than risk reformatting (or corrupting) the file.
     */
    private function registerComposerScript(): void
    {
        $command = '@php artisan puff:publish --ansi';
        $path = base_path('composer.json');

        if (! is_file($path)) {
            $this->manualScriptHint($command);

            return;
        }

        $content = (string) file_get_contents($path);

        if (str_contains($content, $command)) {
            $this->components->info('puff:publish is already in composer post-update-cmd.');

            return;
        }

        // Match a non-empty post-update-cmd array (the `"` requires a first
        // entry) and capture the indentation of that entry, so the new line
        // lines up with its siblings and nothing else in the file is touched.
        if (preg_match('/"post-update-cmd"\s*:\s*\[\s*\n([ \t]*)"/', $content, $m, PREG_OFFSET_CAPTURE) !== 1) {
            $this->manualScriptHint($command);

            return;
        }

        $indent = $m[1][0];
        $insertAt = (int) $m[1][1];
        $entry = $indent.'"'.$command.'",'."\n";

        file_put_contents($path, substr_replace($content, $entry, $insertAt, 0));

        $this->components->info('Added puff:publish to composer post-update-cmd.');
    }

    private function manualScriptHint(string $command): void
    {
        $this->components->warn(
            'Add "'.$command.'" to the post-update-cmd scripts in composer.json so the '
            .'stub stays in sync on composer update (see the README).'
        );
    }

    /**
     * Resolve the stack to install: an explicit --stack always wins, otherwise
     * auto-detect from the app so the Vue/React starter kits just work. Returns
     * null when the stack can't be determined, so the caller can fail loudly
     * rather than guess.
     */
    private function resolveStack(): ?string
    {
        if ($override = $this->option('stack')) {
            return strtolower((string) $override);
        }

        return $this->detectStack();
    }

    /**
     * Sniff the app for its frontend stack. A .tsx/.jsx entry is a definitive
     * React signal; anything else is corroborated against package.json
     * dependencies rather than assumed. Returns null when nothing is
     * conclusive, so the caller fails loudly instead of guessing.
     */
    private function detectStack(): ?string
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

            $hasReact = isset($deps['react']);
            $hasVue = isset($deps['vue']);

            if ($hasReact && ! $hasVue) {
                return 'react';
            }

            if ($hasVue && ! $hasReact) {
                return 'vue';
            }
        }

        return null;
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

        $module = '@/laravel-puff/puff';
        $import = "import { startPuff } from '{$module}';";

        $content = $this->insertImport($content, $import, $module);

        // Call it at module scope, after the app has been created.
        $content = rtrim($content, "\n")
            ."\n\n// Warm the scale-to-zero stack whenever a visitor is active.\nstartPuff();\n";

        file_put_contents($entry, $content);

        $this->components->info("Wired startPuff() into {$relative}.");
    }

    /**
     * Insert an import line where eslint import/order wants it (the rule the
     * starter kits use). Preference order: sorted among the existing `@/` alias
     * (internal) imports; else before the first relative import (the internal
     * group sorts ahead of parent/sibling); else after the last import; else at
     * the top of the file.
     */
    private function insertImport(string $content, string $import, string $module): string
    {
        if (preg_match_all('/^[ \t]*import\b[^;\n]*;[ \t]*$/m', $content, $all, PREG_OFFSET_CAPTURE) < 1) {
            return $import."\n".$content;
        }

        $insertBefore = null;
        $insertAfterEnd = null;
        $firstRelativeOffset = null;

        foreach ($all[0] as [$line, $offset]) {
            if (preg_match('/from\s*[\'"]([^\'"]+)[\'"]/', $line, $m) !== 1) {
                continue;
            }

            $specifier = $m[1];

            if (str_starts_with($specifier, '@/')) {
                // First alias import that sorts after ours: slot in before it.
                if (strcmp($specifier, $module) > 0) {
                    $insertBefore = $offset;
                    break;
                }

                // Otherwise remember the end of the alias block seen so far.
                $insertAfterEnd = $offset + strlen($line);

                continue;
            }

            // Our `@/` import is in the "internal" group, which sorts before the
            // "parent"/"sibling" groups, so note the first relative import as the
            // boundary to use when there are no alias imports to sort among.
            if ($firstRelativeOffset === null
                && (str_starts_with($specifier, './') || str_starts_with($specifier, '../'))) {
                $firstRelativeOffset = $offset;
            }
        }

        // Found an alias import that sorts after ours: slot in right before it.
        if ($insertBefore !== null) {
            return substr_replace($content, $import."\n", $insertBefore, 0);
        }

        // Alias imports exist but ours sorts last: append after the alias block.
        if ($insertAfterEnd !== null) {
            return substr_replace($content, "\n".$import, $insertAfterEnd, 0);
        }

        // No alias imports: place ours before the first relative import (i.e.
        // after the external/builtin imports) so import/order stays happy.
        if ($firstRelativeOffset !== null) {
            return substr_replace($content, $import."\n", $firstRelativeOffset, 0);
        }

        // Only external imports: fall back to after the last import.
        $last = end($all[0]);

        return substr_replace($content, "\n".$import, $last[1] + strlen($last[0]), 0);
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
