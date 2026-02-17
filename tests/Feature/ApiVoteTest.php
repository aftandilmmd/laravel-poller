<?php

use Aftandilmmd\PollVote\Http\Controllers\Api\PollVoteController;
use Aftandilmmd\PollVote\Models\Poll;
use Aftandilmmd\PollVote\Models\PollOption;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::group(['prefix' => 'api/polls', 'middleware' => ['api']], function () {
        Route::post('/{poll}/vote', [PollVoteController::class, 'store']);
        Route::put('/{poll}/vote', [PollVoteController::class, 'update']);
        Route::delete('/{poll}/vote', [PollVoteController::class, 'destroy']);
        Route::get('/{poll}/votes', [PollVoteController::class, 'index']);
    });

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
    ]);

    $this->optionB = PollOption::factory()->create([
        'poll_id' => $this->poll->id,
        'title' => 'Option B',
    ]);
});

// ── Cast Vote ──────────────────────────────────────────────────────

it('casts a vote via API', function () {
    $this->actingAs($this->user)
        ->postJson("/api/polls/{$this->poll->id}/vote", [
            'options' => [$this->optionA->id],
        ])
        ->assertCreated();

    expect($this->optionA->fresh()->votes_count)->toBe(1);
});

it('casts a vote with comment', function () {
    $this->actingAs($this->user)
        ->postJson("/api/polls/{$this->poll->id}/vote", [
            'options' => [$this->optionA->id],
            'comment' => 'Great choice!',
        ])
        ->assertCreated();
});

it('returns 422 for invalid vote', function () {
    $this->actingAs($this->user)
        ->postJson("/api/polls/{$this->poll->id}/vote", [
            'options' => [999],
        ])
        ->assertUnprocessable();
});

it('returns 422 for duplicate vote', function () {
    app('poll-vote')->castVote($this->poll, $this->user, $this->optionA->id);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$this->poll->id}/vote", [
            'options' => [$this->optionB->id],
        ])
        ->assertUnprocessable();
});

// ── Change Vote ────────────────────────────────────────────────────

it('changes a vote via API', function () {
    $this->poll->update(['allow_vote_change' => true]);

    app('poll-vote')->castVote($this->poll, $this->user, $this->optionA->id);

    $this->actingAs($this->user)
        ->putJson("/api/polls/{$this->poll->id}/vote", [
            'options' => [$this->optionB->id],
        ])
        ->assertSuccessful();

    expect($this->optionA->fresh()->votes_count)->toBe(0);
    expect($this->optionB->fresh()->votes_count)->toBe(1);
});

it('returns 422 when vote changing is not allowed', function () {
    $this->poll->update(['allow_vote_change' => false]);
    config()->set('poll-vote.features.vote_changing', false);

    app('poll-vote')->castVote($this->poll, $this->user, $this->optionA->id);

    $this->actingAs($this->user)
        ->putJson("/api/polls/{$this->poll->id}/vote", [
            'options' => [$this->optionB->id],
        ])
        ->assertUnprocessable();
});

// ── Retract Vote ───────────────────────────────────────────────────

it('retracts a vote via API', function () {
    app('poll-vote')->castVote($this->poll, $this->user, $this->optionA->id);

    $this->actingAs($this->user)
        ->deleteJson("/api/polls/{$this->poll->id}/vote")
        ->assertNoContent();

    expect($this->optionA->fresh()->votes_count)->toBe(0);
});

it('returns 422 when retraction is disabled', function () {
    config()->set('poll-vote.features.vote_retraction', false);

    app('poll-vote')->castVote($this->poll, $this->user, $this->optionA->id);

    $this->actingAs($this->user)
        ->deleteJson("/api/polls/{$this->poll->id}/vote")
        ->assertUnprocessable();
});

// ── Vote List ──────────────────────────────────────────────────────

it('lists votes for a non-anonymous poll', function () {
    app('poll-vote')->castVote($this->poll, $this->user, $this->optionA->id);

    $this->actingAs($this->user)
        ->getJson("/api/polls/{$this->poll->id}/votes")
        ->assertSuccessful();
});

it('returns 403 for votes on anonymous poll', function () {
    $poll = Poll::factory()->active()->singleChoice()->anonymous()->create([
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->getJson("/api/polls/{$poll->id}/votes")
        ->assertForbidden();
});

// ── Multiple Choice Vote ───────────────────────────────────────────

it('casts multiple choice vote via API', function () {
    $poll = Poll::factory()->active()->multipleChoice()->create([
        'created_by' => $this->user->id,
    ]);

    $opt1 = PollOption::factory()->create(['poll_id' => $poll->id]);
    $opt2 = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$poll->id}/vote", [
            'options' => [$opt1->id, $opt2->id],
        ])
        ->assertCreated();
});

// ── Rating Vote ────────────────────────────────────────────────────

it('casts rating vote via API', function () {
    $poll = Poll::factory()->active()->rating()->create([
        'created_by' => $this->user->id,
    ]);

    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$poll->id}/vote", [
            'options' => [$option->id],
            'rating' => 4,
        ])
        ->assertCreated();
});
