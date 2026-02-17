<?php

use Aftandilmmd\PollVote\Exceptions\InvalidSelectionException;
use Aftandilmmd\PollVote\Exceptions\PollException;
use Aftandilmmd\PollVote\Models\Poll;
use Aftandilmmd\PollVote\Models\PollOption;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

// ── max_votes_per_user ─────────────────────────────────────────────

it('enforces max_votes_per_user limit', function () {
    $poll = Poll::factory()->active()->singleChoice()->create([
        'created_by' => $this->user->id,
        'max_votes_per_user' => 1,
        'allow_vote_change' => true,
    ]);

    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poll-vote')->castVote($poll, $this->user, $option->id);

    $voter2 = User::forceCreate([
        'name' => 'Voter 2',
        'email' => 'voter2@example.com',
        'password' => 'password',
    ]);

    // Second user can vote (different user)
    app('poll-vote')->castVote($poll, $voter2, $option->id);

    expect($option->fresh()->votes_count)->toBe(2);
});

// ── requires_comment ───────────────────────────────────────────────

it('rejects vote without comment when requires_comment is enabled', function () {
    $poll = Poll::factory()->active()->singleChoice()->withComment()->create([
        'created_by' => $this->user->id,
    ]);

    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poll-vote')->castVote($poll, $this->user, $option->id);
})->throws(InvalidSelectionException::class, 'comment is required');

it('accepts vote with comment when requires_comment is enabled', function () {
    $poll = Poll::factory()->active()->singleChoice()->withComment()->create([
        'created_by' => $this->user->id,
    ]);

    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $votes = app('poll-vote')->castVote($poll, $this->user, $option->id, ['comment' => 'My comment']);

    expect($votes->first()->comment)->toBe('My comment');
});

// ── Rating validation ──────────────────────────────────────────────

it('rejects rating outside configured range', function () {
    $poll = Poll::factory()->active()->rating()->create([
        'created_by' => $this->user->id,
    ]);

    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poll-vote')->castVote($poll, $this->user, $option->id, ['rating' => 10]);
})->throws(InvalidSelectionException::class, 'Rating must be between');

it('accepts rating within configured range', function () {
    $poll = Poll::factory()->active()->rating()->create([
        'created_by' => $this->user->id,
    ]);

    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $votes = app('poll-vote')->castVote($poll, $this->user, $option->id, ['rating' => 3]);

    expect($votes->first()->rating)->toBe(3);
});

it('respects custom rating range from config', function () {
    config()->set('poll-vote.rating.min', 1);
    config()->set('poll-vote.rating.max', 10);

    $poll = Poll::factory()->active()->rating()->create([
        'created_by' => $this->user->id,
    ]);

    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $votes = app('poll-vote')->castVote($poll, $this->user, $option->id, ['rating' => 8]);

    expect($votes->first()->rating)->toBe(8);
});

// ── Type change prevention ─────────────────────────────────────────

it('prevents type change on poll with votes', function () {
    $poll = Poll::factory()->active()->singleChoice()->create([
        'created_by' => $this->user->id,
    ]);

    $option = PollOption::factory()->create(['poll_id' => $poll->id]);
    app('poll-vote')->castVote($poll, $this->user, $option->id);

    app('poll-vote')->update($poll, ['type' => 'multiple_choice']);
})->throws(PollException::class, 'Cannot change poll type');

it('allows type change on poll without votes', function () {
    $poll = Poll::factory()->draft()->singleChoice()->create([
        'created_by' => $this->user->id,
    ]);

    $updated = app('poll-vote')->update($poll, ['type' => 'multiple_choice']);

    expect($updated->type->value)->toBe('multiple_choice');
});

// ── Selection limits ───────────────────────────────────────────────

it('rejects too many selections on multiple choice', function () {
    $poll = Poll::factory()->active()->multipleChoice()->create([
        'created_by' => $this->user->id,
        'max_selections' => 2,
    ]);

    $opt1 = PollOption::factory()->create(['poll_id' => $poll->id]);
    $opt2 = PollOption::factory()->create(['poll_id' => $poll->id]);
    $opt3 = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poll-vote')->castVote($poll, $this->user, [$opt1->id, $opt2->id, $opt3->id]);
})->throws(InvalidSelectionException::class, 'At most 2 options');

it('rejects options from different poll', function () {
    $poll = Poll::factory()->active()->singleChoice()->create([
        'created_by' => $this->user->id,
    ]);

    $otherPoll = Poll::factory()->active()->create(['created_by' => $this->user->id]);
    $foreignOption = PollOption::factory()->create(['poll_id' => $otherPoll->id]);

    app('poll-vote')->castVote($poll, $this->user, $foreignOption->id);
})->throws(InvalidSelectionException::class, 'do not belong to this poll');

// ── Vote change validation ─────────────────────────────────────────

it('requires comment on vote change when poll requires comment', function () {
    $poll = Poll::factory()->active()->singleChoice()->withComment()->withVoteChange()->create([
        'created_by' => $this->user->id,
    ]);

    $optA = PollOption::factory()->create(['poll_id' => $poll->id]);
    $optB = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poll-vote')->castVote($poll, $this->user, $optA->id, ['comment' => 'First comment']);
    app('poll-vote')->changeVote($poll, $this->user, $optB->id);
})->throws(InvalidSelectionException::class, 'comment is required');

// ── Ranked voting ──────────────────────────────────────────────────

it('casts ranked votes with rank values', function () {
    $poll = Poll::factory()->active()->ranked()->create([
        'created_by' => $this->user->id,
        'min_selections' => 1,
        'max_selections' => 3,
    ]);

    $opt1 = PollOption::factory()->create(['poll_id' => $poll->id]);
    $opt2 = PollOption::factory()->create(['poll_id' => $poll->id]);

    $votes = app('poll-vote')->castVote($poll, $this->user, [$opt1->id, $opt2->id], [
        'ranks' => [1, 2],
    ]);

    expect($votes)->toHaveCount(2);
    expect($votes[0]->rank)->toBe(1);
    expect($votes[1]->rank)->toBe(2);
});
