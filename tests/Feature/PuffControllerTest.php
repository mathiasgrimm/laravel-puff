<?php

use Illuminate\Auth\GenericUser;
use MathiasGrimm\Puff\Tests\TestCase;

uses(TestCase::class);

it('returns no content for an authenticated user', function () {
    $this->actingAs(new GenericUser(['id' => 1]))
        ->postJson('/puff')
        ->assertNoContent();
});

it('rejects an unauthenticated request', function () {
    $this->postJson('/puff')->assertUnauthorized();
});

it('still succeeds when a warm target throws', function () {
    config()->set('puff.warm.database', ['does-not-exist']);

    $this->actingAs(new GenericUser(['id' => 1]))
        ->postJson('/puff')
        ->assertNoContent();
});
