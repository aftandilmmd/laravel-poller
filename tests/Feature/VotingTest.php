<?php

use Aftandilmmd\Larapoll\Exceptions\AlreadyVotedException;
use Aftandilmmd\Larapoll\Exceptions\InvalidSelectionException;
use Aftandilmmd\Larapoll\Exceptions\PollClosedException;
use Aftandilmmd\Larapoll\Models\Poll;
use Aftandilmmd\Larapoll\Models\PollOption;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->poll = Poll::factory()->active()->singleChoice()->create([
        'created_by' => $this->user->id,
    ]);

    $this->optionA = PollOption::factory()->create([
        'poll_id' => $this->poll->id,
        'title' => 'Option A',
        'sort_order' => 0,
    ]);

    $this->optionB = PollOption::factory()->create([
        'poll_id' => $this->poll->id,
        'title' => 'Option B',
        'sort_order' => 1,
    ]);
});

it('casts a vote on single choice poll', function () {
    $votes = app('larapoll')->castVote($this->poll, $this->user, $this->optionA->id);

    expect($votes)->toHaveCount(1);
    expect($votes->first()->poll_option_id)->toBe($this->optionA->id);
    expect($this->optionA->fresh()->votes_count)->toBe(1);
});

it('prevents duplicate voting', function () {
    app('larapoll')->castVote($this->poll, $this->user, $this->optionA->id);

    app('larapoll')->castVote($this->poll, $this->user, $this->optionB->id);
})->throws(AlreadyVotedException::class);

it('prevents voting on closed poll', function () {
    $this->poll->close();

    app('larapoll')->castVote($this->poll, $this->user, $this->optionA->id);
})->throws(PollClosedException::class);

it('prevents multiple selections on single choice poll', function () {
    app('larapoll')->castVote($this->poll, $this->user, [$this->optionA->id, $this->optionB->id]);
})->throws(InvalidSelectionException::class);

it('allows multiple selections on multiple choice poll', function () {
    $poll = Poll::factory()->active()->multipleChoice()->create([
        'created_by' => $this->user->id,
    ]);

    $optA = PollOption::factory()->create(['poll_id' => $poll->id]);
    $optB = PollOption::factory()->create(['poll_id' => $poll->id]);

    $votes = app('larapoll')->castVote($poll, $this->user, [$optA->id, $optB->id]);

    expect($votes)->toHaveCount(2);
});

it('changes a vote when allowed', function () {
    $this->poll->update(['allow_vote_change' => true]);

    app('larapoll')->castVote($this->poll, $this->user, $this->optionA->id);

    expect($this->optionA->fresh()->votes_count)->toBe(1);
    expect($this->optionB->fresh()->votes_count)->toBe(0);

    $newVotes = app('larapoll')->changeVote($this->poll, $this->user, $this->optionB->id);

    expect($newVotes)->toHaveCount(1);
    expect($newVotes->first()->poll_option_id)->toBe($this->optionB->id);
    expect($this->optionA->fresh()->votes_count)->toBe(0);
    expect($this->optionB->fresh()->votes_count)->toBe(1);
});

it('retracts a vote', function () {
    app('larapoll')->castVote($this->poll, $this->user, $this->optionA->id);

    expect($this->optionA->fresh()->votes_count)->toBe(1);

    app('larapoll')->retractVote($this->poll, $this->user);

    expect($this->optionA->fresh()->votes_count)->toBe(0);
    expect($this->poll->hasUserVoted($this->user))->toBeFalse();
});

it('casts vote with comment', function () {
    $votes = app('larapoll')->castVote(
        $this->poll,
        $this->user,
        $this->optionA->id,
        ['comment' => 'Great option!'],
    );

    expect($votes->first()->comment)->toBe('Great option!');
});

it('casts vote with rating', function () {
    $poll = Poll::factory()->active()->rating()->create([
        'created_by' => $this->user->id,
    ]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $votes = app('larapoll')->castVote(
        $poll,
        $this->user,
        $option->id,
        ['rating' => 4],
    );

    expect($votes->first()->rating)->toBe(4);
});

it('validates min selections', function () {
    $poll = Poll::factory()->active()->multipleChoice()->create([
        'created_by' => $this->user->id,
        'min_selections' => 2,
        'max_selections' => 3,
    ]);

    $optA = PollOption::factory()->create(['poll_id' => $poll->id]);
    PollOption::factory()->create(['poll_id' => $poll->id]);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    app('larapoll')->castVote($poll, $this->user, [$optA->id]);
})->throws(InvalidSelectionException::class);
