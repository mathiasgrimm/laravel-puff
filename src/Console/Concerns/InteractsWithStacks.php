<?php

namespace MathiasGrimm\Puff\Console\Concerns;

trait InteractsWithStacks
{
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
     * Determine which stack is already installed by reading the published
     * adapter, so a re-publish keeps the original choice. Returns null when
     * Puff has not been installed yet.
     */
    private function installedStack(): ?string
    {
        $adapter = resource_path('js/laravel-puff/usePuff.ts');

        if (! is_file($adapter)) {
            return null;
        }

        $contents = (string) file_get_contents($adapter);

        if (str_contains($contents, "from 'react'")) {
            return 'react';
        }

        if (str_contains($contents, "from 'vue'")) {
            return 'vue';
        }

        return null;
    }
}
