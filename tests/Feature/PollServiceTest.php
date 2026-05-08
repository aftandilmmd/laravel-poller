<?php

use Aftandilmmd\Poller\Enums\PollStatus;
use Aftandilmmd\Poller\Enums\PollType;
use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

it('creates a poll via service', function () {
    $poll = app('poller')->create([
        'title' => 'Test Poll',
        'type' => PollType::SingleChoice,
    ], $this->user);

    expect($poll)
        ->toBeInstanceOf(Poll::class)
        ->title->toBe('Test Poll')
        ->type->toBe(PollType::SingleChoice)
        ->status->toBe(PollStatus::Draft)
        ->created_by->toBe($this->user->id);
});

it('updates a poll', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);

    $updated = app('poller')->update($poll, ['title' => 'Updated Title']);

    expect($updated->title)->toBe('Updated Title');
});

it('deletes a poll', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);

    app('poller')->delete($poll);

    expect(Poll::find($poll->id))->toBeNull();
    expect(Poll::withTrashed()->find($poll->id))->not->toBeNull();
});

it('duplicates a poll with options', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);
    PollOption::factory()->count(3)->create(['poll_id' => $poll->id]);

    $duplicate = app('poller')->duplicate($poll);

    expect($duplicate)
        ->id->not->toBe($poll->id)
        ->title->toBe($poll->title)
        ->status->toBe(PollStatus::Draft);

    expect($duplicate->options)->toHaveCount(3);
});

it('adds an option to a poll', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);

    $option = app('poller')->addOption($poll, [
        'title' => 'Option A',
        'description' => 'Description A',
    ]);

    expect($option)
        ->toBeInstanceOf(PollOption::class)
        ->title->toBe('Option A')
        ->poll_id->toBe($poll->id);
});

it('reorders options', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);
    $opt1 = PollOption::factory()->create(['poll_id' => $poll->id, 'sort_order' => 0]);
    $opt2 = PollOption::factory()->create(['poll_id' => $poll->id, 'sort_order' => 1]);
    $opt3 = PollOption::factory()->create(['poll_id' => $poll->id, 'sort_order' => 2]);

    app('poller')->reorderOptions($poll, [$opt3->id, $opt1->id, $opt2->id]);

    expect($opt3->fresh()->sort_order)->toBe(0);
    expect($opt1->fresh()->sort_order)->toBe(1);
    expect($opt2->fresh()->sort_order)->toBe(2);
});

it('gets active polls', function () {
    Poll::factory()->active()->count(3)->create(['created_by' => $this->user->id]);
    Poll::factory()->draft()->count(2)->create(['created_by' => $this->user->id]);

    $active = app('poller')->getActivePolls();

    expect($active)->toHaveCount(3);
});
