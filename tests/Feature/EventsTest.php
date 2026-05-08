<?php

use Aftandilmmd\Poller\Events\PollActivated;
use Aftandilmmd\Poller\Events\PollCancelled;
use Aftandilmmd\Poller\Events\PollClosed;
use Aftandilmmd\Poller\Events\PollCreated;
use Aftandilmmd\Poller\Events\VoteCast;
use Aftandilmmd\Poller\Events\VoteChanged;
use Aftandilmmd\Poller\Events\VoteRetracted;
use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

it('dispatches PollCreated event when poll is created', function () {
    Event::fake([PollCreated::class]);

    $poll = app('poller')->create(['title' => 'Test'], $this->user);

    Event::assertDispatched(PollCreated::class, function ($event) use ($poll) {
        return $event->poll->id === $poll->id && $event->creator->getAuthIdentifier() === $this->user->id;
    });
});

it('dispatches PollActivated event when poll is activated', function () {
    Event::fake([PollActivated::class]);

    $poll = Poll::factory()->draft()->create(['created_by' => $this->user->id]);

    app('poller')->activate($poll);

    Event::assertDispatched(PollActivated::class, fn ($event) => $event->poll->id === $poll->id);
});

it('dispatches PollActivated event via model method', function () {
    Event::fake([PollActivated::class]);

    $poll = Poll::factory()->draft()->create(['created_by' => $this->user->id]);

    $poll->activate();

    Event::assertDispatched(PollActivated::class);
});

it('dispatches PollClosed event when poll is closed', function () {
    Event::fake([PollClosed::class]);

    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    app('poller')->close($poll);

    Event::assertDispatched(PollClosed::class, fn ($event) => $event->poll->id === $poll->id);
});

it('dispatches PollClosed event via model method', function () {
    Event::fake([PollClosed::class]);

    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    $poll->close();

    Event::assertDispatched(PollClosed::class);
});

it('dispatches PollCancelled event when poll is cancelled', function () {
    Event::fake([PollCancelled::class]);

    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    app('poller')->cancel($poll);

    Event::assertDispatched(PollCancelled::class, fn ($event) => $event->poll->id === $poll->id);
});

it('dispatches PollCancelled event via model method', function () {
    Event::fake([PollCancelled::class]);

    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    $poll->cancel();

    Event::assertDispatched(PollCancelled::class);
});

it('dispatches VoteCast event when a vote is cast', function () {
    Event::fake([VoteCast::class]);

    $poll = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poller')->castVote($poll, $this->user, $option->id);

    Event::assertDispatched(VoteCast::class, function ($event) use ($poll) {
        return $event->poll->id === $poll->id
            && $event->voter->getAuthIdentifier() === $this->user->id
            && $event->votes->isNotEmpty();
    });
});

it('dispatches VoteChanged event when a vote is changed', function () {
    Event::fake([VoteCast::class, VoteChanged::class]);

    $poll = Poll::factory()->active()->singleChoice()->withVoteChange()->create([
        'created_by' => $this->user->id,
    ]);

    $optA = PollOption::factory()->create(['poll_id' => $poll->id]);
    $optB = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poller')->castVote($poll, $this->user, $optA->id);
    app('poller')->changeVote($poll, $this->user, $optB->id);

    Event::assertDispatched(VoteChanged::class);
});

it('dispatches VoteRetracted event when a vote is retracted', function () {
    Event::fake([VoteCast::class, VoteRetracted::class]);

    $poll = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poller')->castVote($poll, $this->user, $option->id);
    app('poller')->retractVote($poll, $this->user);

    Event::assertDispatched(VoteRetracted::class, function ($event) use ($poll) {
        return $event->poll->id === $poll->id
            && $event->voter->getAuthIdentifier() === $this->user->id;
    });
});

it('does not dispatch event when event class is null in config', function () {
    config()->set('poller.events.poll_created', null);

    Event::fake([PollCreated::class]);

    app('poller')->create(['title' => 'Test'], $this->user);

    Event::assertNotDispatched(PollCreated::class);
});
