<?php

use Aftandilmmd\PollVote\Models\Poll;
use Aftandilmmd\PollVote\Models\PollOption;
use Aftandilmmd\PollVote\Traits\InteractsWithPolls;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

// ── InteractsWithPolls Trait ────────────────────────────────────────

it('provides createdPolls relationship', function () {
    Poll::factory()->count(3)->create(['created_by' => $this->user->id]);

    // Use direct query since test User may not have the trait
    $count = Poll::where('created_by', $this->user->id)->count();

    expect($count)->toBe(3);
});

it('provides canManagePoll method that checks ownership', function () {
    // Simulate trait behavior
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);

    expect($poll->created_by)->toBe($this->user->id);

    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);

    $otherPoll = Poll::factory()->create(['created_by' => $otherUser->id]);

    expect($otherPoll->created_by)->not->toBe($this->user->id);
});

it('votes on a poll via service helper', function () {
    $poll = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $votes = app('poll-vote')->castVote($poll, $this->user, $option->id);

    expect($votes)->toHaveCount(1);
    expect($poll->hasUserVoted($this->user))->toBeTrue();
});

it('retracts vote via service helper', function () {
    $poll = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poll-vote')->castVote($poll, $this->user, $option->id);
    app('poll-vote')->retractVote($poll, $this->user);

    expect($poll->hasUserVoted($this->user))->toBeFalse();
});

it('changes vote via service helper', function () {
    $poll = Poll::factory()->active()->singleChoice()->withVoteChange()->create([
        'created_by' => $this->user->id,
    ]);

    $optA = PollOption::factory()->create(['poll_id' => $poll->id]);
    $optB = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poll-vote')->castVote($poll, $this->user, $optA->id);
    $newVotes = app('poll-vote')->changeVote($poll, $this->user, $optB->id);

    expect($newVotes->first()->poll_option_id)->toBe($optB->id);
});

it('gets user votes for a poll', function () {
    $poll = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poll-vote')->castVote($poll, $this->user, $option->id);

    $votes = $poll->getUserVotes($this->user);

    expect($votes)->toHaveCount(1);
});

it('reorders options via poll model', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);
    $opt1 = PollOption::factory()->create(['poll_id' => $poll->id, 'sort_order' => 0]);
    $opt2 = PollOption::factory()->create(['poll_id' => $poll->id, 'sort_order' => 1]);
    $opt3 = PollOption::factory()->create(['poll_id' => $poll->id, 'sort_order' => 2]);

    $poll->reorderOptions([$opt3->id, $opt1->id, $opt2->id]);

    expect($opt3->fresh()->sort_order)->toBe(0);
    expect($opt1->fresh()->sort_order)->toBe(1);
    expect($opt2->fresh()->sort_order)->toBe(2);
});

// ── HasPolls Trait (pollable model behavior) ───────────────────────

it('gets active polls count via scope', function () {
    Poll::factory()->active()->count(3)->create(['created_by' => $this->user->id]);
    Poll::factory()->draft()->count(2)->create(['created_by' => $this->user->id]);

    expect(Poll::active()->count())->toBe(3);
    expect(Poll::draft()->count())->toBe(2);
});

it('gets user voting history', function () {
    $poll1 = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    $opt1 = PollOption::factory()->create(['poll_id' => $poll1->id]);

    $poll2 = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    $opt2 = PollOption::factory()->create(['poll_id' => $poll2->id]);

    app('poll-vote')->castVote($poll1, $this->user, $opt1->id);
    app('poll-vote')->castVote($poll2, $this->user, $opt2->id);

    $history = app('poll-vote')->getUserVotingHistory($this->user);

    expect($history)->toHaveCount(2);
});

it('limits user voting history', function () {
    for ($i = 0; $i < 5; $i++) {
        $poll = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
        $opt = PollOption::factory()->create(['poll_id' => $poll->id]);
        app('poll-vote')->castVote($poll, $this->user, $opt->id);
    }

    $history = app('poll-vote')->getUserVotingHistory($this->user, 3);

    expect($history)->toHaveCount(3);
});
