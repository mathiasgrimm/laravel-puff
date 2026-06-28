<?php

use MathiasGrimm\Puff\Tests\ThrottleLimitTestCase;

uses(ThrottleLimitTestCase::class);

it('returns 429 once the throttle limit is exceeded', function () {
    $this->postJson('/puff')->assertNoContent();
    $this->postJson('/puff')->assertNoContent();

    // Third request is over the 2-per-minute limit.
    $this->postJson('/puff')->assertStatus(429);
});
