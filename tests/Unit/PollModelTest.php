<?php

use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

it('checks type helpers', function () {
    $poll = Poll::factory()->yesNo()->create(['created_by' => $this->user->id]);

    expect($poll->isYesNo())->toBeTrue();
    expect($poll->isSingleChoice())->toBeFalse();
    expect($poll->allowsMultipleSelections())->toBeFalse();
});

it('checks status helpers', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    expect($poll->isActive())->toBeTrue();
    expect($poll->isDraft())->toBeFalse();
    expect($poll->isClosed())->toBeFalse();
});

it('calculates results as percentages', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    $optA = PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 7]);
    $optB = PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 3]);

    $results = $poll->getResultsAsPercentages();

    expect($results)->toHaveCount(2);
    expect($results[0]['percentage'])->toBe(70.0);
    expect($results[1]['percentage'])->toBe(30.0);
});

it('finds leading option', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 5, 'title' => 'Low']);
    PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 15, 'title' => 'High']);

    expect($poll->getLeadingOption()->title)->toBe('High');
});

it('returns total votes', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 10]);
    PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 20]);

    expect($poll->getTotalVotes())->toBe(30);
});

it('checks multiple selections', function () {
    $multiple = Poll::factory()->multipleChoice()->create(['created_by' => $this->user->id]);
    $ranked = Poll::factory()->ranked()->create(['created_by' => $this->user->id]);
    $single = Poll::factory()->singleChoice()->create(['created_by' => $this->user->id]);

    expect($multiple->allowsMultipleSelections())->toBeTrue();
    expect($ranked->allowsMultipleSelections())->toBeTrue();
    expect($single->allowsMultipleSelections())->toBeFalse();
});

it('can show results when closed', function () {
    $poll = Poll::factory()->closed()->create(['created_by' => $this->user->id]);

    expect($poll->canShowResults())->toBeTrue();
});

it('can show results when show_results_before_close is enabled', function () {
    $poll = Poll::factory()->active()->liveResults()->create(['created_by' => $this->user->id]);

    expect($poll->canShowResults())->toBeTrue();
});

it('cannot show results for active poll without live results', function () {
    $poll = Poll::factory()->active()->create([
        'created_by' => $this->user->id,
        'show_results_before_close' => false,
    ]);

    expect($poll->canShowResults())->toBeFalse();
});

it('applies scopes correctly', function () {
    Poll::factory()->active()->count(2)->create(['created_by' => $this->user->id]);
    Poll::factory()->draft()->count(3)->create(['created_by' => $this->user->id]);
    Poll::factory()->closed()->create(['created_by' => $this->user->id]);

    expect(Poll::active()->count())->toBe(2);
    expect(Poll::draft()->count())->toBe(3);
    expect(Poll::closed()->count())->toBe(1);
});
