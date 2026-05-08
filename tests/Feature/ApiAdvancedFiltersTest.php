<?php

use Aftandilmmd\Poller\Enums\PollStatus;
use Aftandilmmd\Poller\Enums\PollType;
use Aftandilmmd\Poller\Http\Controllers\Api\PollController;
use Aftandilmmd\Poller\Http\Controllers\Api\PollVoteController;
use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::group(['prefix' => 'api/polls', 'middleware' => ['api']], function () {
        Route::get('/', [PollController::class, 'index']);
        Route::post('/{poll}/vote', [PollVoteController::class, 'store']);
    });

    $this->user = User::forceCreate([
        'name' => 'Filterer',
        'email' => 'filter@example.com',
        'password' => 'password',
    ]);
});

it('filters polls by search term across title and description', function () {
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'PHP frameworks', 'description' => null]);
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'Other', 'description' => 'mentions php here']);
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'Unrelated', 'description' => null]);

    $response = $this->actingAs($this->user)->getJson('/api/polls?search=php');

    expect($response->json('data'))->toHaveCount(2);
});

it('filters polls by status using new scope', function () {
    Poll::factory()->create(['created_by' => $this->user->id, 'status' => PollStatus::Draft]);
    Poll::factory()->create(['created_by' => $this->user->id, 'status' => PollStatus::Active]);
    Poll::factory()->create(['created_by' => $this->user->id, 'status' => PollStatus::Active]);

    $response = $this->actingAs($this->user)->getJson('/api/polls?status=active');

    expect($response->json('data'))->toHaveCount(2);
});

it('filters polls by type', function () {
    Poll::factory()->create(['created_by' => $this->user->id, 'type' => PollType::SingleChoice]);
    Poll::factory()->create(['created_by' => $this->user->id, 'type' => PollType::YesNo]);

    $response = $this->actingAs($this->user)->getJson('/api/polls?type=yes_no');

    expect($response->json('data'))->toHaveCount(1);
});

it('filters polls by created_by parameter', function () {
    $other = User::forceCreate(['name' => 'Other', 'email' => 'o@e.com', 'password' => 'pw']);

    Poll::factory()->count(2)->create(['created_by' => $this->user->id]);
    Poll::factory()->create(['created_by' => $other->id]);

    $response = $this->actingAs($this->user)->getJson('/api/polls?created_by='.$other->id);

    expect($response->json('data'))->toHaveCount(1);
});

it('filters polls within date range', function () {
    $old = Poll::factory()->create(['created_by' => $this->user->id]);
    $old->forceFill(['created_at' => now()->subMonths(2)])->save();

    $recent = Poll::factory()->create(['created_by' => $this->user->id]);
    $recent->forceFill(['created_at' => now()->subDays(1)])->save();

    $from = now()->subWeek()->toIso8601String();
    $to = now()->toIso8601String();

    $response = $this->actingAs($this->user)->getJson('/api/polls?from='.urlencode($from).'&to='.urlencode($to));

    expect($response->json('data'))->toHaveCount(1);
});

it('returns 429 when voter rate limit exceeded', function () {
    config()->set('poller.voter_rate_limit.enabled', true);
    config()->set('poller.voter_rate_limit.max_votes', 1);

    $pollA = app('poller')->create([
        'title' => 'A',
        'status' => PollStatus::Active,
    ], $this->user);
    PollOption::factory()->for($pollA)->create();
    $optionA = $pollA->options()->first();

    $pollB = app('poller')->create([
        'title' => 'B',
        'status' => PollStatus::Active,
    ], $this->user);
    PollOption::factory()->for($pollB)->create();
    $optionB = $pollB->options()->first();

    $first = $this->actingAs($this->user)
        ->postJson('/api/polls/'.$pollA->id.'/vote', ['options' => [$optionA->id]]);
    $first->assertCreated();

    $second = $this->actingAs($this->user)
        ->postJson('/api/polls/'.$pollB->id.'/vote', ['options' => [$optionB->id]]);
    $second->assertStatus(429);
});
