<?php

use Aftandilmmd\Poller\Enums\PollStatus;
use Aftandilmmd\Poller\Events\PollCreated;
use Aftandilmmd\Poller\Events\VoteCast;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Broadcaster',
        'email' => 'broadcast@example.com',
        'password' => 'password',
    ]);
});

it('events implement ShouldBroadcast', function () {
    expect(new \ReflectionClass(PollCreated::class))->implementsInterface(ShouldBroadcast::class)->toBeTrue();
    expect(new \ReflectionClass(VoteCast::class))->implementsInterface(ShouldBroadcast::class)->toBeTrue();
});

it('broadcasts on private channel by default', function () {
    config()->set('poller.broadcasting.enabled', true);
    config()->set('poller.broadcasting.channel', 'private');

    $poll = app('poller')->create(['title' => 'Channel test'], $this->user);
    $event = new PollCreated($poll, $this->user);

    $channel = $event->broadcastOn();
    expect($channel)->toBeInstanceOf(PrivateChannel::class);
    expect($channel->name)->toContain('private-poller.poll.'.$poll->id);
});

it('broadcasts on presence channel when configured', function () {
    config()->set('poller.broadcasting.enabled', true);
    config()->set('poller.broadcasting.channel', 'presence');

    $poll = app('poller')->create(['title' => 'Presence'], $this->user);
    $event = new PollCreated($poll, $this->user);

    expect($event->broadcastOn())->toBeInstanceOf(PresenceChannel::class);
});

it('broadcastWhen returns false when broadcasting disabled', function () {
    config()->set('poller.broadcasting.enabled', false);

    $poll = app('poller')->create(['title' => 'Off'], $this->user);
    $event = new PollCreated($poll, $this->user);

    expect($event->broadcastWhen())->toBeFalse();
});

it('broadcastWhen returns true when broadcasting enabled', function () {
    config()->set('poller.broadcasting.enabled', true);

    $poll = app('poller')->create(['title' => 'On'], $this->user);
    $event = new PollCreated($poll, $this->user);

    expect($event->broadcastWhen())->toBeTrue();
});

it('vote event uses configured prefix', function () {
    config()->set('poller.broadcasting.enabled', true);
    config()->set('poller.broadcasting.channel_prefix', 'app.polls');

    $poll = app('poller')->create([
        'title' => 'Prefix',
        'status' => PollStatus::Active,
    ], $this->user);

    $opt = PollOption::factory()->for($poll)->create();
    $votes = app('poller')->castVote($poll->fresh(), $this->user, $opt->id);

    $event = new VoteCast($poll, $this->user, $votes);
    expect($event->broadcastOn()->name)->toContain('app.polls.'.$poll->id);
});
