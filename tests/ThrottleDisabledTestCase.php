<?php

namespace MathiasGrimm\Puff\Tests;

use Illuminate\Foundation\Application;

class ThrottleDisabledTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('puff.throttle', null);
    }
}
