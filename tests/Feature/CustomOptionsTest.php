<?php

use Aftandilmmd\PollVote\Exceptions\CustomOptionException;
use Aftandilmmd\PollVote\Exceptions\PollClosedException;
use Aftandilmmd\PollVote\Models\Poll;
use Aftandilmmd\PollVote\Models\PollOption;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->poll = Poll::factory()->active()->singleChoice()->withCustomOptions(5)->create([
        'created_by' => $this->user->id,
    ]);

    PollOption::factory()->create([
        'poll_id' => $this->poll->id,
        'title' => 'Option A',
        'sort_order' => 0,
    ]);
});

it('adds a custom option to a poll', function () {
    $option = app('poll-vote')->addCustomOption($this->poll, $this->user, ['title' => 'My Custom Option']);

    expect($option)->toBeInstanceOf(PollOption::class);
    expect($option->title)->toBe('My Custom Option');
    expect($option->is_custom)->toBeTrue();
    expect($option->created_by)->toBe($this->user->id);
    expect($option->poll_id)->toBe($this->poll->id);
});

it('prevents custom options when not allowed on poll', function () {
    $poll = Poll::factory()->active()->singleChoice()->create([
        'created_by' => $this->user->id,
        'allow_custom_options' => false,
    ]);

    app('poll-vote')->addCustomOption($poll, $this->user, ['title' => 'Should fail']);
})->throws(CustomOptionException::class, 'Custom options are not allowed for this poll.');

it('prevents custom options when feature is disabled globally', function () {
    config()->set('poll-vote.features.custom_options', false);

    app('poll-vote')->addCustomOption($this->poll, $this->user, ['title' => 'Should fail']);
})->throws(CustomOptionException::class, 'Custom options are not allowed for this poll.');

it('prevents custom options on closed poll', function () {
    $this->poll->close();

    app('poll-vote')->addCustomOption($this->poll, $this->user, ['title' => 'Should fail']);
})->throws(PollClosedException::class);

it('enforces max custom options limit', function () {
    $poll = Poll::factory()->active()->singleChoice()->withCustomOptions(2)->create([
        'created_by' => $this->user->id,
    ]);

    app('poll-vote')->addCustomOption($poll, $this->user, ['title' => 'Custom 1']);
    app('poll-vote')->addCustomOption($poll, $this->user, ['title' => 'Custom 2']);
    app('poll-vote')->addCustomOption($poll, $this->user, ['title' => 'Custom 3']);
})->throws(CustomOptionException::class, 'maximum of 2 custom options');

it('allows unlimited custom options when max is null', function () {
    $poll = Poll::factory()->active()->singleChoice()->withCustomOptions()->create([
        'created_by' => $this->user->id,
    ]);

    for ($i = 1; $i <= 10; $i++) {
        app('poll-vote')->addCustomOption($poll, $this->user, ['title' => "Custom {$i}"]);
    }

    expect($poll->customOptions()->count())->toBe(10);
});

it('auto assigns sort order to custom options', function () {
    $option = app('poll-vote')->addCustomOption($this->poll, $this->user, ['title' => 'Custom']);

    expect($option->sort_order)->toBe(1);
});

it('counts only custom options toward the limit', function () {
    $poll = Poll::factory()->active()->singleChoice()->withCustomOptions(2)->create([
        'created_by' => $this->user->id,
    ]);

    PollOption::factory()->create(['poll_id' => $poll->id, 'title' => 'Regular 1']);
    PollOption::factory()->create(['poll_id' => $poll->id, 'title' => 'Regular 2']);

    app('poll-vote')->addCustomOption($poll, $this->user, ['title' => 'Custom 1']);
    app('poll-vote')->addCustomOption($poll, $this->user, ['title' => 'Custom 2']);

    expect($poll->options()->count())->toBe(4);
    expect($poll->customOptions()->count())->toBe(2);
    expect($poll->hasReachedCustomOptionLimit())->toBeTrue();
});

it('checks allowsCustomOptions helper', function () {
    expect($this->poll->allowsCustomOptions())->toBeTrue();

    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'allow_custom_options' => false,
    ]);
    expect($poll->allowsCustomOptions())->toBeFalse();
});

it('identifies custom options via isCustom helper', function () {
    $regular = PollOption::factory()->create(['poll_id' => $this->poll->id]);
    $custom = PollOption::factory()->custom($this->user->id)->create(['poll_id' => $this->poll->id]);

    expect($regular->isCustom())->toBeFalse();
    expect($custom->isCustom())->toBeTrue();
});

it('includes custom option fields in duplicate', function () {
    $newPoll = $this->poll->duplicate();

    expect($newPoll->allow_custom_options)->toBeTrue();
    expect($newPoll->max_custom_options)->toBe(5);
});
