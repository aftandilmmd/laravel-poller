<?php

use Aftandilmmd\Poller\Enums\PollStatus;
use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Aftandilmmd\Poller\Models\PollVote;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

// ── Auto Open Command ──────────────────────────────────────────────

it('activates polls whose start time has passed', function () {
    Poll::factory()->create([
        'created_by' => $this->user->id,
        'status' => PollStatus::Draft,
        'starts_at' => now()->subMinute(),
    ]);

    Poll::factory()->create([
        'created_by' => $this->user->id,
        'status' => PollStatus::Draft,
        'starts_at' => now()->addDay(),
    ]);

    $this->artisan('poller:auto-open')
        ->expectsOutputToContain('Activated 1 poll(s)')
        ->assertSuccessful();

    expect(Poll::where('status', PollStatus::Active)->count())->toBe(1);
});

it('does not activate when auto_open is disabled', function () {
    config()->set('poller.features.auto_open', false);

    Poll::factory()->create([
        'created_by' => $this->user->id,
        'status' => PollStatus::Draft,
        'starts_at' => now()->subMinute(),
    ]);

    $this->artisan('poller:auto-open')
        ->expectsOutputToContain('disabled')
        ->assertSuccessful();

    expect(Poll::where('status', PollStatus::Active)->count())->toBe(0);
});

// ── Auto Close Command ─────────────────────────────────────────────

it('closes polls whose end time has passed', function () {
    Poll::factory()->active()->create([
        'created_by' => $this->user->id,
        'ends_at' => now()->subMinute(),
    ]);

    Poll::factory()->active()->create([
        'created_by' => $this->user->id,
        'ends_at' => now()->addDay(),
    ]);

    $this->artisan('poller:auto-close')
        ->expectsOutputToContain('Closed 1 poll(s)')
        ->assertSuccessful();

    expect(Poll::where('status', PollStatus::Closed)->count())->toBe(1);
});

it('does not close when auto_close is disabled', function () {
    config()->set('poller.features.auto_close', false);

    Poll::factory()->active()->create([
        'created_by' => $this->user->id,
        'ends_at' => now()->subMinute(),
    ]);

    $this->artisan('poller:auto-close')
        ->expectsOutputToContain('disabled')
        ->assertSuccessful();
});

// ── Reconcile Counts Command ───────────────────────────────────────

it('reconciles vote counts from actual vote records', function () {
    $poll = Poll::factory()->active()->create(['created_by' => $this->user->id]);

    $optionA = PollOption::factory()->create([
        'poll_id' => $poll->id,
        'votes_count' => 999,
    ]);

    $optionB = PollOption::factory()->create([
        'poll_id' => $poll->id,
        'votes_count' => 0,
    ]);

    // Create 3 actual votes for optionA
    for ($i = 0; $i < 3; $i++) {
        $voter = User::forceCreate([
            'name' => "Voter {$i}",
            'email' => "voter{$i}@example.com",
            'password' => 'password',
        ]);

        PollVote::forceCreate([
            'poll_id' => $poll->id,
            'poll_option_id' => $optionA->id,
            'user_id' => $voter->id,
        ]);
    }

    $this->artisan('poller:reconcile-counts')
        ->expectsOutputToContain('Reconciled vote counts')
        ->assertSuccessful();

    expect($optionA->fresh()->votes_count)->toBe(3);
    expect($optionB->fresh()->votes_count)->toBe(0);
});
