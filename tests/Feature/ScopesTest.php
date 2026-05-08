<?php

use Aftandilmmd\Poller\Enums\PollStatus;
use Aftandilmmd\Poller\Enums\PollType;
use Aftandilmmd\Poller\Models\Poll;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Searcher',
        'email' => 'search@example.com',
        'password' => 'password',
    ]);

    $this->other = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
});

it('search scope matches title and description', function () {
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'PHP framework choice', 'description' => null]);
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'Other', 'description' => 'mentions php here']);
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'Unrelated', 'description' => 'nope']);

    expect(Poll::query()->search('php')->count())->toBe(2);
    expect(Poll::query()->search(null)->count())->toBe(3);
    expect(Poll::query()->search('')->count())->toBe(3);
});

it('ofStatus scope filters by enum or string', function () {
    Poll::factory()->create(['created_by' => $this->user->id, 'status' => PollStatus::Draft]);
    Poll::factory()->create(['created_by' => $this->user->id, 'status' => PollStatus::Active]);
    Poll::factory()->create(['created_by' => $this->user->id, 'status' => PollStatus::Active]);

    expect(Poll::query()->ofStatus(PollStatus::Active)->count())->toBe(2);
    expect(Poll::query()->ofStatus('draft')->count())->toBe(1);
    expect(Poll::query()->ofStatus(null)->count())->toBe(3);
});

it('ofType scope filters by enum or string', function () {
    Poll::factory()->create(['created_by' => $this->user->id, 'type' => PollType::SingleChoice]);
    Poll::factory()->create(['created_by' => $this->user->id, 'type' => PollType::MultipleChoice]);
    Poll::factory()->create(['created_by' => $this->user->id, 'type' => PollType::YesNo]);

    expect(Poll::query()->ofType(PollType::YesNo)->count())->toBe(1);
    expect(Poll::query()->ofType('multiple_choice')->count())->toBe(1);
});

it('createdBy scope filters by user id', function () {
    Poll::factory()->count(2)->create(['created_by' => $this->user->id]);
    Poll::factory()->create(['created_by' => $this->other->id]);

    expect(Poll::query()->createdBy($this->user->id)->count())->toBe(2);
    expect(Poll::query()->createdBy($this->other->id)->count())->toBe(1);
});

it('withinDateRange scope filters by created_at', function () {
    $old = Poll::factory()->create(['created_by' => $this->user->id]);
    $old->forceFill(['created_at' => now()->subMonths(2)])->save();

    $recent = Poll::factory()->create(['created_by' => $this->user->id]);
    $recent->forceFill(['created_at' => now()->subDays(1)])->save();

    expect(Poll::query()->withinDateRange(now()->subWeek(), now())->count())->toBe(1);
    expect(Poll::query()->withinDateRange(now()->subMonths(3), null)->count())->toBe(2);
    expect(Poll::query()->withinDateRange(null, now()->subMonth())->count())->toBe(1);
});

it('scopes are chainable', function () {
    Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => 'PHP rocks',
        'status' => PollStatus::Active,
        'type' => PollType::SingleChoice,
    ]);

    Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => 'PHP draft',
        'status' => PollStatus::Draft,
        'type' => PollType::SingleChoice,
    ]);

    $count = Poll::query()
        ->search('php')
        ->ofStatus(PollStatus::Active)
        ->ofType(PollType::SingleChoice)
        ->createdBy($this->user->id)
        ->count();

    expect($count)->toBe(1);
});
