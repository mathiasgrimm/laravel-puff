<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use MathiasGrimm\Puff\Tests\TestCase;

uses(TestCase::class);

it('returns no content for a guest', function () {
    $this->postJson('/puff')->assertNoContent();
});

it('returns no content for an authenticated user', function () {
    $this->actingAs(new GenericUser(['id' => 1]))
        ->postJson('/puff')
        ->assertNoContent();
});

it('still succeeds when warm targets are unreachable', function () {
    config()->set('puff.warm.database.connections', ['does-not-exist']);
    config()->set('puff.warm.redis.connections', ['does-not-exist']);

    $this->postJson('/puff')->assertNoContent();
});

it('runs the database warmer when enabled', function () {
    config()->set('puff.warm.redis.enabled', false);

    DB::connection()->flushQueryLog();
    DB::connection()->enableQueryLog();

    $this->postJson('/puff')->assertNoContent();

    expect(collect(DB::connection()->getQueryLog())->pluck('query'))
        ->toContain('select 1');
});

it('skips the database warmer when disabled', function () {
    config()->set('puff.warm.database.enabled', false);
    config()->set('puff.warm.redis.enabled', false);

    DB::connection()->flushQueryLog();
    DB::connection()->enableQueryLog();

    $this->postJson('/puff')->assertNoContent();

    expect(DB::connection()->getQueryLog())->toBeEmpty();
});
