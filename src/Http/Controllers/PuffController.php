<?php

namespace MathiasGrimm\Puff\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PuffController
{
    /**
     * Proactively warm the scale-to-0 stack ahead of user interaction.
     *
     * Each backing-service touch is wrapped and swallowed: during a cold start
     * the service may still be spinning up, and the connection attempt itself
     * is what triggers the platform to scale it, so we must not turn that into a
     * 500.
     */
    public function __invoke(): Response
    {
        $this->warm('database', fn (?string $connection) => DB::connection($connection)->select('select 1'));

        $this->warm('redis', fn (?string $connection) => Redis::connection($connection)->command('ping'));

        return response()->noContent();
    }

    /**
     * Touch every configured connection for a warmer, if it is enabled. An empty
     * connection list falls back to the default connection (null).
     *
     * @param  callable(?string): mixed  $touch
     */
    private function warm(string $service, callable $touch): void
    {
        if (! config("puff.warm.{$service}.enabled", false)) {
            return;
        }

        /** @var list<string|null> $connections */
        $connections = (array) config("puff.warm.{$service}.connections", []);

        if ($connections === []) {
            $connections = [null];
        }

        foreach ($connections as $connection) {
            try {
                $touch($connection);
            } catch (\Throwable) {
                // Cold start. The attempt itself wakes the service.
            }
        }
    }
}
