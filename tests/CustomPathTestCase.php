<?php

namespace MathiasGrimm\Puff\Tests;

use Illuminate\Foundation\Application;

class CustomPathTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('puff.path', 'warm-up');
        $app['config']->set('puff.name', 'warm-up');
    }
}
