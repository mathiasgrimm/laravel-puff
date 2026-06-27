<?php

namespace MathiasGrimm\Puff\Tests;

use Illuminate\Foundation\Application;
use MathiasGrimm\Puff\PuffServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PuffServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // The `web` middleware group encrypts cookies, which needs an app key.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
