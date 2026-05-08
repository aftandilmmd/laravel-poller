<?php

use Aftandilmmd\Poller\Livewire\PollDisplay;
use Aftandilmmd\Poller\Livewire\PollForm;
use Aftandilmmd\Poller\Livewire\PollManager;
use Aftandilmmd\Poller\Livewire\PollResults;
use Aftandilmmd\Poller\Livewire\PollVote;
use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

// ── PollManager ────────────────────────────────────────────────────

it('renders poll manager component', function () {
    Poll::factory()->count(3)->create(['created_by' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->assertSuccessful()
        ->assertViewHas('polls');
});

it('searches polls in manager', function () {
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'Favorite Color']);
    Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'Best Movie']);

    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->set('search', 'Color')
        ->assertViewHas('polls', fn ($polls) => $polls->count() === 1);
});

it('filters polls by status in manager', function () {
    Poll::factory()->active()->count(2)->create(['created_by' => $this->user->id]);
    Poll::factory()->draft()->create(['created_by' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->set('statusFilter', 'active')
        ->assertViewHas('polls', fn ($polls) => $polls->count() === 2);
});

it('opens create form in manager', function () {
    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->call('createPoll')
        ->assertSet('showForm', true)
        ->assertSet('editingPollId', null);
});

it('opens edit form in manager', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->call('editPoll', $poll->id)
        ->assertSet('showForm', true)
        ->assertSet('editingPollId', $poll->id);
});

it('deletes own poll in manager', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->call('deletePoll', $poll->id);

    expect(Poll::find($poll->id))->toBeNull();
});

it('prevents deleting another users poll in manager', function () {
    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $poll = Poll::factory()->create(['created_by' => $otherUser->id]);

    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->call('deletePoll', $poll->id)
        ->assertForbidden();
});

it('activates own poll in manager', function () {
    $poll = Poll::factory()->draft()->create(['created_by' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->call('activatePoll', $poll->id);

    expect($poll->fresh()->isActive())->toBeTrue();
});

it('closes own poll in manager', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->call('closePoll', $poll->id);

    expect($poll->fresh()->isClosed())->toBeTrue();
});

it('duplicates own poll in manager', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id]);
    PollOption::factory()->count(2)->create(['poll_id' => $poll->id]);

    Livewire::actingAs($this->user)
        ->test(PollManager::class, ['pollable' => null])
        ->call('duplicatePoll', $poll->id);

    expect(Poll::count())->toBe(2);
});

// ── PollForm ───────────────────────────────────────────────────────

it('renders poll form component', function () {
    Livewire::actingAs($this->user)
        ->test(PollForm::class)
        ->assertSuccessful();
});

it('creates a poll via form', function () {
    Livewire::actingAs($this->user)
        ->test(PollForm::class)
        ->set('title', 'New Poll')
        ->set('type', 'single_choice')
        ->set('options', [
            ['title' => 'Option A', 'description' => ''],
            ['title' => 'Option B', 'description' => ''],
        ])
        ->call('save')
        ->assertDispatched('poll-saved');

    expect(Poll::count())->toBe(1);
    expect(PollOption::count())->toBe(2);
});

it('validates title is required on form', function () {
    Livewire::actingAs($this->user)
        ->test(PollForm::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

it('validates minimum options on form', function () {
    Livewire::actingAs($this->user)
        ->test(PollForm::class)
        ->set('title', 'Test')
        ->set('type', 'single_choice')
        ->set('options', [['title' => 'Only one', 'description' => '']])
        ->call('save')
        ->assertHasErrors(['options']);
});

it('loads existing poll for editing', function () {
    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => 'Edit Me',
    ]);
    PollOption::factory()->create(['poll_id' => $poll->id, 'title' => 'Opt 1']);
    PollOption::factory()->create(['poll_id' => $poll->id, 'title' => 'Opt 2']);

    Livewire::actingAs($this->user)
        ->test(PollForm::class, ['pollId' => $poll->id])
        ->assertSet('title', 'Edit Me')
        ->assertSet('pollId', $poll->id);
});

it('prevents editing another users poll via form', function () {
    $otherUser = User::forceCreate([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $poll = Poll::factory()->create([
        'created_by' => $otherUser->id,
        'title' => 'Not Yours',
    ]);
    PollOption::factory()->count(2)->create(['poll_id' => $poll->id]);

    Livewire::actingAs($this->user)
        ->test(PollForm::class, ['pollId' => $poll->id])
        ->set('title', 'Hacked')
        ->set('options', [
            ['title' => 'Option A', 'description' => ''],
            ['title' => 'Option B', 'description' => ''],
        ])
        ->call('save')
        ->assertForbidden();
});

it('adds and removes options in form', function () {
    Livewire::actingAs($this->user)
        ->test(PollForm::class)
        ->assertCount('options', 2)
        ->call('addOption')
        ->assertCount('options', 3)
        ->call('removeOption', 0)
        ->assertCount('options', 2);
});

it('cancels form and dispatches event', function () {
    Livewire::actingAs($this->user)
        ->test(PollForm::class)
        ->call('cancel')
        ->assertDispatched('poll-form-closed');
});

// ── PollDisplay ────────────────────────────────────────────────────

it('renders poll display component', function () {
    $poll = Poll::factory()->active()->liveResults()->create(['created_by' => $this->user->id]);
    PollOption::factory()->count(2)->create(['poll_id' => $poll->id]);

    Livewire::actingAs($this->user)
        ->test(PollDisplay::class, ['poll' => $poll])
        ->assertSuccessful()
        ->assertViewHas('canShowResults', true);
});

it('switches tabs in display', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    Livewire::actingAs($this->user)
        ->test(PollDisplay::class, ['poll' => $poll])
        ->assertSet('activeTab', 'overview')
        ->call('setTab', 'results')
        ->assertSet('activeTab', 'results');
});

// ── PollResults ────────────────────────────────────────────────────

it('renders poll results component', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);
    PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 10]);
    PollOption::factory()->create(['poll_id' => $poll->id, 'votes_count' => 5]);

    Livewire::actingAs($this->user)
        ->test(PollResults::class, ['poll' => $poll])
        ->assertSuccessful()
        ->assertViewHas('totalVotes', 15);
});

// ── PollVote ───────────────────────────────────────────────────────

it('renders poll vote component', function () {
    $poll = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    Livewire::actingAs($this->user)
        ->test(PollVote::class, ['poll' => $poll])
        ->assertSuccessful()
        ->assertViewHas('canVote', true);
});

it('submits a vote via component', function () {
    $poll = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    Livewire::actingAs($this->user)
        ->test(PollVote::class, ['poll' => $poll])
        ->set('selectedOption', $option->id)
        ->call('submitVote')
        ->assertDispatched('vote-cast');

    expect($option->fresh()->votes_count)->toBe(1);
});

it('retracts a vote via component', function () {
    $poll = Poll::factory()->active()->singleChoice()->create(['created_by' => $this->user->id]);
    $option = PollOption::factory()->create(['poll_id' => $poll->id]);

    app('poller')->castVote($poll, $this->user, $option->id);

    Livewire::actingAs($this->user)
        ->test(PollVote::class, ['poll' => $poll])
        ->call('retractVote')
        ->assertDispatched('vote-retracted');

    expect($option->fresh()->votes_count)->toBe(0);
});

it('adds custom option via component', function () {
    $poll = Poll::factory()->active()->singleChoice()->withCustomOptions()->create([
        'created_by' => $this->user->id,
    ]);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    Livewire::actingAs($this->user)
        ->test(PollVote::class, ['poll' => $poll])
        ->set('customOptionTitle', 'My Custom Option')
        ->call('addCustomOption')
        ->assertDispatched('custom-option-added');

    expect($poll->customOptions()->count())->toBe(1);
});

it('shows error for empty custom option title', function () {
    $poll = Poll::factory()->active()->singleChoice()->withCustomOptions()->create([
        'created_by' => $this->user->id,
    ]);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    Livewire::actingAs($this->user)
        ->test(PollVote::class, ['poll' => $poll])
        ->set('customOptionTitle', '')
        ->call('addCustomOption')
        ->assertSet('errorMessage', __('poller::messages.please_enter_option_title'));
});

it('toggles results visibility', function () {
    $poll = Poll::factory()->active()->liveResults()->create(['created_by' => $this->user->id]);
    PollOption::factory()->create(['poll_id' => $poll->id]);

    Livewire::actingAs($this->user)
        ->test(PollVote::class, ['poll' => $poll])
        ->assertSet('showResults', false)
        ->call('toggleResults')
        ->assertSet('showResults', true);
});
