<?php

namespace Aftandilmmd\Poller\Traits;

use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Aftandilmmd\Poller\Models\PollVote;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

trait InteractsWithPolls
{
    public function pollVotes(): HasMany
    {
        return $this->hasMany(config('poller.models.vote', PollVote::class), 'user_id');
    }

    public function createdPolls(): HasMany
    {
        return $this->hasMany(config('poller.models.poll', Poll::class), 'created_by');
    }

    public function canCreatePoll(): bool
    {
        return true;
    }

    public function canVote(Poll $poll): bool
    {
        return $poll->isVotingOpen();
    }

    public function canViewResults(Poll $poll): bool
    {
        return true;
    }

    public function canAddCustomOption(Poll $poll): bool
    {
        return $poll->allowsCustomOptions();
    }

    public function canManagePoll(Poll $poll): bool
    {
        return $poll->created_by === $this->getAuthIdentifier();
    }

    public function hasVotedOn(Poll $poll): bool
    {
        return $poll->hasUserVoted($this);
    }

    public function getVotesFor(Poll $poll): Collection
    {
        return $poll->getUserVotes($this);
    }

    /**
     * @param  PollOption|int|array<int|PollOption>  $options
     * @param  array{comment?: string, rank?: int, rating?: int, metadata?: array<string, mixed>}  $extra
     */
    public function vote(Poll $poll, PollOption|int|array $options, array $extra = []): Collection
    {
        return App::make('poller')->castVote($poll, $this, $options, $extra);
    }

    public function retractVote(Poll $poll): void
    {
        App::make('poller')->retractVote($poll, $this);
    }

    /**
     * @param  PollOption|int|array<int|PollOption>  $options
     * @param  array{comment?: string, rank?: int, rating?: int, metadata?: array<string, mixed>}  $extra
     */
    public function changeVote(Poll $poll, PollOption|int|array $options, array $extra = []): Collection
    {
        return App::make('poller')->changeVote($poll, $this, $options, $extra);
    }

    public function addCustomOption(Poll $poll, array $attributes): PollOption
    {
        return App::make('poller')->addCustomOption($poll, $this, $attributes);
    }
}
