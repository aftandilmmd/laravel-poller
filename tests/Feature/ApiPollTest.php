<?php

use Aftandilmmd\Larapoll\Http\Controllers\Api\PollController;
use Aftandilmmd\Larapoll\Models\Poll;
use Aftandilmmd\Larapoll\Models\PollOption;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::group(['prefix' => 'api/polls', 'middleware' => ['api']], function () {
        Route::get('/', [PollController::class, 'index']);
        Route::post('/', [PollController::class, 'store']);
        Route::get('/{poll}', [PollController::class, 'show']);
        Route::put('/{poll}', [PollController::class, 'update']);
        Route::delete('/{poll}', [PollController::class, 'destroy']);
        Route::post('/{poll}/activate', [PollController::class, 'activate']);
        Route::post('/{poll}/close', [PollController::class, 'close']);
        Route::post('/{poll}/cancel', [PollController::class, 'cancel']);
        Route::post('/{poll}/duplicate', [PollController::class, 'duplicate']);
        Route::get('/{poll}/results', [PollController::class, 'results']);
    });

    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

// ── Index ──────────────────────────────────────────────────────────

it('lists polls', function () {
    Poll::factory()->count(3)->create(['created_by' => $this->user->id]);

    $this->actingAs($this->user)
        ->getJson('/api/polls')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('filters polls by status', function () {
    Poll::factory()->active()->count(2)->create(['created_by' => $this->user->id]);
    Poll::factory()->draft()->create(['created_by' => $this->user->id]);

    $this->actingAs($this->user)
        ->getJson('/api/polls?status=active')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('filters polls by type', function () {
    Poll::factory()->singleChoice()->create(['created_by' => $this->user->id]);
    Poll::factory()->multipleChoice()->create(['created_by' => $this->user->id]);

    $this->actingAs($this->user)
        ->getJson('/api/polls?type=single_choice')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('filters polls by mine', function () {
    Poll::factory()->create(['created_by' => $this->user->id]);

    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    Poll::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->user)
        ->getJson('/api/polls?mine=1')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('searches polls by title', function () {
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'Favorite Color']);
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'Best Movie']);

    $this->actingAs($this->user)
        ->getJson('/api/polls?search=Color')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

// ── Store ──────────────────────────────────────────────────────────

it('creates a poll', function () {
    $this->actingAs($this->user)
        ->postJson('/api/polls', [
            'title' => 'New Poll',
            'type' => 'single_choice',
            'options' => [
                ['title' => 'Option A'],
                ['title' => 'Option B'],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'New Poll');

    expect(Poll::count())->toBe(1);
    expect(PollOption::count())->toBe(2);
});

it('validates required fields on store', function () {
    $this->actingAs($this->user)
        ->postJson('/api/polls', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'type']);
});

// ── Show ──────────────────────────────────────────────────────────

it('shows a poll', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);
    PollOption::factory()->count(2)->create(['poll_id' => $poll->id]);

    $this->actingAs($this->user)
        ->getJson("/api/polls/{$poll->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $poll->id)
        ->assertJsonCount(2, 'data.options');
});

// ── Update ─────────────────────────────────────────────────────────

it('updates own poll', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);

    $this->actingAs($this->user)
        ->putJson("/api/polls/{$poll->id}", [
            'title' => 'Updated Title',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Updated Title');
});

it('prevents updating another users poll', function () {
    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $poll = Poll::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->user)
        ->putJson("/api/polls/{$poll->id}", [
            'title' => 'Hacked',
        ])
        ->assertForbidden();
});

// ── Destroy ────────────────────────────────────────────────────────

it('deletes own poll', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);

    $this->actingAs($this->user)
        ->deleteJson("/api/polls/{$poll->id}")
        ->assertNoContent();

    expect(Poll::find($poll->id))->toBeNull();
});

it('prevents deleting another users poll', function () {
    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $poll = Poll::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->user)
        ->deleteJson("/api/polls/{$poll->id}")
        ->assertForbidden();
});

// ── Lifecycle ──────────────────────────────────────────────────────

it('activates own poll', function () {
    $poll = Poll::factory()->draft()->create(['created_by' => $this->user->id]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$poll->id}/activate")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'active');
});

it('closes own poll', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$poll->id}/close")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'closed');
});

it('cancels own poll', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$poll->id}/cancel")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'cancelled');
});

it('duplicates own poll', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);
    PollOption::factory()->count(2)->create(['poll_id' => $poll->id]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$poll->id}/duplicate")
        ->assertSuccessful();

    expect(Poll::count())->toBe(2);
});

it('prevents lifecycle actions on another users poll', function () {
    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $poll = Poll::factory()->draft()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->user)
        ->postJson("/api/polls/{$poll->id}/activate")
        ->assertForbidden();
});

// ── Results ────────────────────────────────────────────────────────

it('returns poll results', function () {
    $poll = Poll::factory()->active()->liveResults()->create(['created_by' => $this->user->id]);

    PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 5]);
    PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 3]);

    $this->actingAs($this->user)
        ->getJson("/api/polls/{$poll->id}/results")
        ->assertSuccessful()
        ->assertJsonStructure(['total_votes', 'unique_voters', 'options']);
});

// ── Pagination ─────────────────────────────────────────────────────

it('paginates poll listing', function () {
    Poll::factory()->count(25)->create(['created_by' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/polls?per_page=10')
        ->assertSuccessful();

    expect($response->json('meta.per_page'))->toBe(10);
    expect($response->json('data'))->toHaveCount(10);
});
