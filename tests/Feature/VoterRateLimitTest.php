<?php

use Aftandilmmd\Poller\Enums\PollStatus;
use Aftandilmmd\Poller\Exceptions\VoterRateLimitException;
use Aftandilmmd\Poller\Models\PollOption;
use Aftandilmmd\Poller\Support\VoterRateLimiter;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Limited',
        'email' => 'limit@example.com',
        'password' => 'password',
    ]);

    VoterRateLimiter::clear($this->user);
});

function makePollWithOptions(User $user, int $count = 3): array
{
    $poll = app('poller')->create([
        'title' => 'Vote',
        'status' => PollStatus::Active,
    ], $user);

    $options = collect(range(1, $count))->map(fn ($i) => PollOption::factory()->for($poll)->create());

    return [$poll->fresh()->load('options'), $options];
}

it('does not enforce limit when disabled', function () {
    config()->set('poller.voter_rate_limit.enabled', false);

    [$poll, $options] = makePollWithOptions($this->user);

    app('poller')->castVote($poll, $this->user, $options[0]->id);

    expect($poll->fresh()->votes()->count())->toBe(1);
});

it('throws when voter exceeds limit', function () {
    config()->set('poller.voter_rate_limit.enabled', true);
    config()->set('poller.voter_rate_limit.max_votes', 2);
    config()->set('poller.voter_rate_limit.per_minutes', 60);

    [$pollA, $optsA] = makePollWithOptions($this->user);
    [$pollB, $optsB] = makePollWithOptions($this->user);
    [$pollC, $optsC] = makePollWithOptions($this->user);

    app('poller')->castVote($pollA, $this->user, $optsA[0]->id);
    app('poller')->castVote($pollB, $this->user, $optsB[0]->id);

    expect(fn () => app('poller')->castVote($pollC, $this->user, $optsC[0]->id))
        ->toThrow(VoterRateLimitException::class);
});

it('counts attempts across cast and change vote', function () {
    config()->set('poller.voter_rate_limit.enabled', true);
    config()->set('poller.voter_rate_limit.max_votes', 1);

    [$pollA, $optsA] = makePollWithOptions($this->user);
    $pollA->update(['allow_vote_change' => true]);
    [$pollB, $optsB] = makePollWithOptions($this->user);

    app('poller')->castVote($pollA->fresh()->load('options'), $this->user, $optsA[0]->id);

    expect(fn () => app('poller')->castVote($pollB, $this->user, $optsB[0]->id))
        ->toThrow(VoterRateLimitException::class);
});

it('clear resets rate limit for voter', function () {
    config()->set('poller.voter_rate_limit.enabled', true);
    config()->set('poller.voter_rate_limit.max_votes', 1);

    [$pollA, $optsA] = makePollWithOptions($this->user);
    [$pollB, $optsB] = makePollWithOptions($this->user);

    app('poller')->castVote($pollA, $this->user, $optsA[0]->id);
    VoterRateLimiter::clear($this->user);

    app('poller')->castVote($pollB, $this->user, $optsB[0]->id);

    expect($pollB->fresh()->votes()->count())->toBe(1);
});
