<?php

namespace MathiasGrimm\Puff\Tests;

use Illuminate\Foundation\Application;

class RouteDisabledTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('puff.register_route', false);
    }
}
