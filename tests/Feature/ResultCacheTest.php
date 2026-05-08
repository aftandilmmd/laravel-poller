<?php

use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Aftandilmmd\Poller\Support\ResultCache;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Cacher',
        'email' => 'cacher@example.com',
        'password' => 'password',
    ]);

    config()->set('poller.cache.enabled', true);
    config()->set('poller.cache.ttl', 60);
});

it('caches results when enabled and serves from cache', function () {
    $poll = app('poller')->create(['title' => 'Cached?'], $this->user);
    $opt = PollOption::factory()->for($poll)->create(['votes_count' => 3]);

    $first = $poll->getResultsAsPercentages();

    expect(Cache::has(ResultCache::key($poll, 'results')))->toBeTrue();

    PollOption::query()->where('id', $opt->id)->update(['votes_count' => 99]);

    $cached = $poll->getResultsAsPercentages();
    expect($cached[0]['votes_count'])->toBe($first[0]['votes_count']);
});

it('does not cache when disabled', function () {
    config()->set('poller.cache.enabled', false);

    $poll = app('poller')->create(['title' => 'No cache'], $this->user);
    PollOption::factory()->for($poll)->create();

    $poll->getResultsAsPercentages();

    expect(Cache::has(ResultCache::key($poll, 'results')))->toBeFalse();
});

it('invalidates cache when a vote is cast', function () {
    $poll = app('poller')->create([
        'title' => 'Vote me',
        'status' => \Aftandilmmd\Poller\Enums\PollStatus::Active,
    ], $this->user);

    $opt = PollOption::factory()->for($poll)->create();

    $poll->getResultsAsPercentages();
    expect(Cache::has(ResultCache::key($poll, 'results')))->toBeTrue();

    app('poller')->castVote($poll->fresh(), $this->user, $opt->id);

    expect(Cache::has(ResultCache::key($poll, 'results')))->toBeFalse();
});

it('flushes cache via flushResultsCache helper', function () {
    $poll = app('poller')->create(['title' => 'Manual flush'], $this->user);
    PollOption::factory()->for($poll)->create();

    $poll->getResultsAsPercentages();
    expect(Cache::has(ResultCache::key($poll, 'results')))->toBeTrue();

    $poll->flushResultsCache();

    expect(Cache::has(ResultCache::key($poll, 'results')))->toBeFalse();
});
