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
        /** @var list<string|null> $connections */
        $connections = (array) config('puff.warm.database', [null]);

        foreach ($connections as $connection) {
            try {
                DB::connection($connection)->select('select 1');
            } catch (\Throwable) {
                // Cold start. The attempt itself wakes the database.
            }
        }

        /** @var list<string|null> $redisConnections */
        $redisConnections = (array) config('puff.warm.redis', [null]);

        foreach ($redisConnections as $connection) {
            try {
                Redis::connection($connection)->command('ping');
            } catch (\Throwable) {
                // Cold start. The attempt itself wakes Redis.
            }
        }

        return response()->noContent();
    }
}
