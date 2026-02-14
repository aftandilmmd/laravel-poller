<?php

use Aftandilmmd\Larapoll\Enums\PollStatus;
use Aftandilmmd\Larapoll\Events\PollActivated;
use Aftandilmmd\Larapoll\Events\PollCancelled;
use Aftandilmmd\Larapoll\Events\PollClosed;
use Aftandilmmd\Larapoll\Events\PollCreated;
use Aftandilmmd\Larapoll\Models\Poll;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

it('fires PollCreated event', function () {
    Event::fake([PollCreated::class]);

    app('larapoll')->create(['title' => 'Event Test'], $this->user);

    Event::assertDispatched(PollCreated::class);
});

it('activates a draft poll', function () {
    Event::fake([PollActivated::class]);

    $poll = Poll::factory()->draft()->create(['created_by' => $this->user->id]);

    $activated = app('larapoll')->activate($poll);

    expect($activated->status)->toBe(PollStatus::Active);
    expect($activated->starts_at)->not->toBeNull();
    Event::assertDispatched(PollActivated::class);
});

it('closes an active poll', function () {
    Event::fake([PollClosed::class]);

    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    $closed = app('larapoll')->close($poll);

    expect($closed->status)->toBe(PollStatus::Closed);
    expect($closed->closed_at)->not->toBeNull();
    Event::assertDispatched(PollClosed::class);
});

it('cancels a poll', function () {
    Event::fake([PollCancelled::class]);

    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    $cancelled = app('larapoll')->cancel($poll);

    expect($cancelled->status)->toBe(PollStatus::Cancelled);
    Event::assertDispatched(PollCancelled::class);
});

it('identifies scheduled polls', function () {
    $poll = Poll::factory()->scheduled()->create(['created_by' => $this->user->id]);

    expect($poll->isScheduled())->toBeTrue();
    expect($poll->isVotingOpen())->toBeFalse();
});

it('identifies voting is open for active polls within time window', function () {
    $poll = Poll::factory()->active()->create([
        'created_by' => $this->user->id,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);

    expect($poll->isVotingOpen())->toBeTrue();
});

it('identifies voting is closed for expired polls', function () {
    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'status' => PollStatus::Active,
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->subDay(),
    ]);

    expect($poll->hasEnded())->toBeTrue();
    expect($poll->isVotingOpen())->toBeFalse();
});
