<?php

namespace MathiasGrimm\Puff\Tests;

use Illuminate\Foundation\Application;

class ThrottleLimitTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // A tiny limit so the test can exceed it in a few requests.
        $app['config']->set('puff.throttle', '2,1');
    }
}
