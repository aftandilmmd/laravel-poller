<?php

use Aftandilmmd\Poller\Http\Controllers\Api\PollOptionController;
use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::group(['prefix' => 'api/polls', 'middleware' => ['api']], function () {
        Route::post('/{poll}/options', [PollOptionController::class, 'store']);
        Route::put('/{poll}/options/{option}', [PollOptionController::class, 'update']);
        Route::delete('/{poll}/options/{option}', [PollOptionController::class, 'destroy']);
        Route::post('/{poll}/options/reorder', [PollOptionController::class, 'reorder']);
    });

    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->poll = Poll::factory()->create(['created_by' => $this->user->id]);
});

// ── Store ──────────────────────────────────────────────────────────

it('adds an option to own poll', function () {
    $this->actingAs($this->user)
        ->postJson("/api/polls/{$this->poll->id}/options", [
            'title' => 'New Option',
            'description' => 'Description',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'New Option');

    expect($this->poll->options()->count())->toBe(1);
});

it('prevents adding option to another users poll', function () {
    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $otherPoll = Poll::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$otherPoll->id}/options", [
            'title' => 'Hacked Option',
        ])
        ->assertForbidden();
});

it('validates required title on store', function () {
    $this->actingAs($this->user)
        ->postJson("/api/polls/{$this->poll->id}/options", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

// ── Update ─────────────────────────────────────────────────────────

it('updates an option on own poll', function () {
    $option = PollOption::factory()->create(['poll_id' => $this->poll->id]);

    $this->actingAs($this->user)
        ->putJson("/api/polls/{$this->poll->id}/options/{$option->id}", [
            'title' => 'Updated Option',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Updated Option');
});

it('prevents updating option on another users poll', function () {
    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $otherPoll = Poll::factory()->create(['created_by' => $otherUser->id]);
    $option = PollOption::factory()->create(['poll_id' => $otherPoll->id]);

    $this->actingAs($this->user)
        ->putJson("/api/polls/{$otherPoll->id}/options/{$option->id}", [
            'title' => 'Hacked',
        ])
        ->assertForbidden();
});

// ── Destroy ────────────────────────────────────────────────────────

it('deletes an option on own poll', function () {
    $option = PollOption::factory()->create(['poll_id' => $this->poll->id]);

    $this->actingAs($this->user)
        ->deleteJson("/api/polls/{$this->poll->id}/options/{$option->id}")
        ->assertNoContent();

    expect(PollOption::find($option->id))->toBeNull();
});

// ── Option scoping ─────────────────────────────────────────────────

it('returns 404 for option belonging to different poll', function () {
    $otherPoll = Poll::factory()->create(['created_by' => $this->user->id]);
    $foreignOption = PollOption::factory()->create(['poll_id' => $otherPoll->id]);

    $this->actingAs($this->user)
        ->putJson("/api/polls/{$this->poll->id}/options/{$foreignOption->id}", [
            'title' => 'Should fail',
        ])
        ->assertNotFound();
});

// ── Reorder ────────────────────────────────────────────────────────

it('reorders options on own poll', function () {
    $opt1 = PollOption::factory()->create(['poll_id' => $this->poll->id, 'sort_order' => 0]);
    $opt2 = PollOption::factory()->create(['poll_id' => $this->poll->id, 'sort_order' => 1]);
    $opt3 = PollOption::factory()->create(['poll_id' => $this->poll->id, 'sort_order' => 2]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$this->poll->id}/options/reorder", [
            'option_ids' => [$opt3->id, $opt1->id, $opt2->id],
        ])
        ->assertSuccessful();

    expect($opt3->fresh()->sort_order)->toBe(0);
    expect($opt1->fresh()->sort_order)->toBe(1);
    expect($opt2->fresh()->sort_order)->toBe(2);
});

it('prevents reordering on another users poll', function () {
    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $otherPoll = Poll::factory()->create(['created_by' => $otherUser->id]);
    $opt = PollOption::factory()->create(['poll_id' => $otherPoll->id]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$otherPoll->id}/options/reorder", [
            'option_ids' => [$opt->id],
        ])
        ->assertForbidden();
});
