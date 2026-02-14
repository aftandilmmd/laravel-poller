<?php

namespace Aftandilmmd\Larapoll\Services;

use Aftandilmmd\Larapoll\Contracts\LarapollServiceInterface;
use Aftandilmmd\Larapoll\Enums\PollStatus;
use Aftandilmmd\Larapoll\Exceptions\AlreadyVotedException;
use Aftandilmmd\Larapoll\Exceptions\CustomOptionException;
use Aftandilmmd\Larapoll\Exceptions\InvalidSelectionException;
use Aftandilmmd\Larapoll\Exceptions\PollClosedException;
use Aftandilmmd\Larapoll\Exceptions\PollException;
use Aftandilmmd\Larapoll\Exceptions\UnauthorizedVoteException;
use Aftandilmmd\Larapoll\Models\Poll;
use Aftandilmmd\Larapoll\Models\PollOption;
use Aftandilmmd\Larapoll\Models\PollVote;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PollService implements LarapollServiceInterface
{
    // ── CRUD ────────────────────────────────────────────────────────

    public function create(array $attributes, Authenticatable $creator): Poll
    {
        $attributes['created_by'] = $creator->getAuthIdentifier();
        $attributes['status'] = $attributes['status'] ?? PollStatus::Draft;

        /** @var Poll $poll */
        $poll = $this->pollModel()::query()->create($attributes);

        $this->fireEvent('poll_created', $poll, $creator);

        return $poll;
    }

    public function update(Poll $poll, array $attributes): Poll
    {
        if (isset($attributes['type']) && $poll->type->value !== $attributes['type'] && $poll->votes()->exists()) {
            throw new PollException('Cannot change poll type after votes have been cast.');
        }

        $poll->update($attributes);

        return $poll->fresh();
    }

    public function delete(Poll $poll): bool
    {
        if (config('larapoll.features.soft_deletes', true)) {
            return (bool) $poll->delete();
        }

        return (bool) $poll->forceDelete();
    }

    public function duplicate(Poll $poll, ?array $overrides = null): Poll
    {
        return $poll->duplicate($overrides);
    }

    // ── Options ─────────────────────────────────────────────────────

    public function addOption(Poll $poll, array $attributes): PollOption
    {
        if (! isset($attributes['sort_order'])) {
            $attributes['sort_order'] = $poll->options()->max('sort_order') + 1;
        }

        return $poll->options()->create($attributes);
    }

    public function addOptions(Poll $poll, array $options): Collection
    {
        return collect($options)->map(fn (array $attributes) => $this->addOption($poll, $attributes));
    }

    public function updateOption(PollOption $option, array $attributes): PollOption
    {
        $option->update($attributes);

        return $option->fresh();
    }

    public function removeOption(PollOption $option): bool
    {
        return (bool) $option->delete();
    }

    public function reorderOptions(Poll $poll, array $optionIds): void
    {
        foreach ($optionIds as $index => $optionId) {
            $poll->options()->where('id', $optionId)->update(['sort_order' => $index]);
        }
    }

    public function addCustomOption(Poll $poll, Authenticatable $user, array $attributes): PollOption
    {
        if (! $poll->allowsCustomOptions()) {
            throw new CustomOptionException('Custom options are not allowed for this poll.');
        }

        if (! $poll->isVotingOpen()) {
            throw new PollClosedException;
        }

        if (method_exists($user, 'canAddCustomOption') && ! $user->canAddCustomOption($poll)) {
            throw new CustomOptionException('You are not authorized to add custom options to this poll.');
        }

        if ($poll->hasReachedCustomOptionLimit()) {
            throw new CustomOptionException("This poll has reached its maximum of {$poll->max_custom_options} custom options.");
        }

        $attributes['is_custom'] = true;
        $attributes['created_by'] = $user->getAuthIdentifier();

        return $this->addOption($poll, $attributes);
    }

    // ── Lifecycle ───────────────────────────────────────────────────

    public function activate(Poll $poll): Poll
    {
        $poll->activate();

        return $poll;
    }

    public function close(Poll $poll): Poll
    {
        $poll->close();

        return $poll;
    }

    public function cancel(Poll $poll): Poll
    {
        $poll->cancel();

        return $poll;
    }

    // ── Voting ──────────────────────────────────────────────────────

    public function castVote(Poll $poll, Authenticatable $voter, PollOption|int|array $options, array $extra = []): Collection
    {
        $this->validateVote($poll, $voter);
        $this->validateExtra($poll, $extra);

        if ($poll->hasUserVoted($voter)) {
            if ($poll->allow_vote_change && config('larapoll.features.vote_changing', true)) {
                return $this->changeVote($poll, $voter, $options, $extra);
            }

            throw new AlreadyVotedException;
        }

        if ($poll->max_votes_per_user) {
            $existingCount = $poll->votes()
                ->where('user_id', $voter->getAuthIdentifier())
                ->distinct('poll_option_id')
                ->count('poll_option_id');

            if ($existingCount >= $poll->max_votes_per_user) {
                throw new AlreadyVotedException("Maximum of {$poll->max_votes_per_user} votes allowed.");
            }
        }

        $optionIds = $this->resolveOptionIds($options);
        $this->validateSelections($poll, $optionIds);

        return DB::transaction(function () use ($poll, $voter, $optionIds, $extra) {
            $votes = collect();

            foreach ($optionIds as $index => $optionId) {
                /** @var PollVote $vote */
                $vote = $this->voteModel()::query()->create([
                    'poll_id' => $poll->id,
                    'poll_option_id' => $optionId,
                    'user_id' => $voter->getAuthIdentifier(),
                    'comment' => $extra['comment'] ?? null,
                    'rank' => $poll->isRanked() ? ($extra['ranks'][$index] ?? $index + 1) : null,
                    'rating' => $poll->isRating() ? ($extra['rating'] ?? null) : null,
                    'metadata' => $extra['metadata'] ?? null,
                ]);

                $votes->push($vote);

                $this->optionModel()::query()
                    ->where('id', $optionId)
                    ->increment('votes_count');
            }

            $this->fireEvent('vote_cast', $poll, $voter, $votes);

            return $votes;
        });
    }

    public function changeVote(Poll $poll, Authenticatable $voter, PollOption|int|array $options, array $extra = []): Collection
    {
        if (! $poll->allow_vote_change && ! config('larapoll.features.vote_changing', true)) {
            throw new AlreadyVotedException('Vote changing is not allowed for this poll.');
        }

        $this->validateVote($poll, $voter, checkExisting: false);
        $this->validateExtra($poll, $extra);

        $optionIds = $this->resolveOptionIds($options);
        $this->validateSelections($poll, $optionIds);

        return DB::transaction(function () use ($poll, $voter, $optionIds, $extra) {
            $oldVotes = $poll->getUserVotes($voter);

            // Decrement old vote counts
            foreach ($oldVotes as $oldVote) {
                $this->optionModel()::query()
                    ->where('id', $oldVote->poll_option_id)
                    ->decrement('votes_count');
            }

            // Delete old votes
            $poll->votes()->where('user_id', $voter->getAuthIdentifier())->delete();

            // Create new votes
            $votes = collect();
            foreach ($optionIds as $index => $optionId) {
                /** @var PollVote $vote */
                $vote = $this->voteModel()::query()->create([
                    'poll_id' => $poll->id,
                    'poll_option_id' => $optionId,
                    'user_id' => $voter->getAuthIdentifier(),
                    'comment' => $extra['comment'] ?? null,
                    'rank' => $poll->isRanked() ? ($extra['ranks'][$index] ?? $index + 1) : null,
                    'rating' => $poll->isRating() ? ($extra['rating'] ?? null) : null,
                    'metadata' => $extra['metadata'] ?? null,
                ]);

                $votes->push($vote);

                $this->optionModel()::query()
                    ->where('id', $optionId)
                    ->increment('votes_count');
            }

            $this->fireEvent('vote_changed', $poll, $voter, $oldVotes, $votes);

            return $votes;
        });
    }

    public function retractVote(Poll $poll, Authenticatable $voter): void
    {
        if (! config('larapoll.features.vote_retraction', true)) {
            throw new AlreadyVotedException('Vote retraction is not allowed.');
        }

        $this->validateVote($poll, $voter, checkExisting: false);

        DB::transaction(function () use ($poll, $voter) {
            $existingVotes = $poll->getUserVotes($voter);

            foreach ($existingVotes as $vote) {
                $this->optionModel()::query()
                    ->where('id', $vote->poll_option_id)
                    ->decrement('votes_count');
            }

            $poll->votes()->where('user_id', $voter->getAuthIdentifier())->delete();

            $this->fireEvent('vote_retracted', $poll, $voter);
        });
    }

    // ── Results ─────────────────────────────────────────────────────

    public function getResults(Poll $poll): array
    {
        return $poll->getResultsAsPercentages();
    }

    public function getDetailedResults(Poll $poll): array
    {
        $poll->loadMissing(['options', 'votes']);

        return [
            'poll' => $poll,
            'total_votes' => $poll->getTotalVotes(),
            'unique_voters' => $poll->getUniqueVoterCount(),
            'options' => $poll->getResultsAsPercentages(),
            'leading_option' => $poll->getLeadingOption(),
        ];
    }

    public function exportResults(Poll $poll, string $format = 'array'): mixed
    {
        $results = $this->getDetailedResults($poll);

        if ($format === 'collection') {
            return collect($results);
        }

        return $results;
    }

    // ── Queries ─────────────────────────────────────────────────────

    public function getActivePolls(?Model $pollable = null): Collection
    {
        $query = $this->pollModel()::query()->active();

        if ($pollable) {
            $query->where('pollable_type', $pollable->getMorphClass())
                ->where('pollable_id', $pollable->getKey());
        }

        return $query->with('options')->latest()->get();
    }

    public function getUserVotingHistory(Authenticatable $user, ?int $limit = null): Collection
    {
        $query = $this->voteModel()::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->with(['poll', 'option'])
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    // ── Protected Helpers ───────────────────────────────────────────

    protected function validateVote(Poll $poll, Authenticatable $voter, bool $checkExisting = true): void
    {
        if (! $poll->isVotingOpen()) {
            throw new PollClosedException;
        }

        if (method_exists($voter, 'canVote') && ! $voter->canVote($poll)) {
            throw new UnauthorizedVoteException;
        }

        if ($checkExisting && $poll->hasUserVoted($voter)) {
            if (! $poll->allow_vote_change || ! config('larapoll.features.vote_changing', true)) {
                throw new AlreadyVotedException;
            }
        }
    }

    protected function validateExtra(Poll $poll, array $extra): void
    {
        if ($poll->requires_comment && empty($extra['comment'])) {
            throw new InvalidSelectionException('A comment is required for this poll.');
        }

        if ($poll->isRating() && isset($extra['rating'])) {
            $min = config('larapoll.rating.min', 1);
            $max = config('larapoll.rating.max', 5);

            if ($extra['rating'] < $min || $extra['rating'] > $max) {
                throw new InvalidSelectionException("Rating must be between {$min} and {$max}.");
            }
        }
    }

    /**
     * @param  PollOption|int|array<int|PollOption>  $options
     * @return array<int>
     */
    protected function resolveOptionIds(PollOption|int|array $options): array
    {
        if ($options instanceof PollOption) {
            return [$options->id];
        }

        if (is_int($options)) {
            return [$options];
        }

        return collect($options)->map(fn ($option) => $option instanceof PollOption ? $option->id : $option)->toArray();
    }

    /**
     * @param  array<int>  $optionIds
     */
    protected function validateSelections(Poll $poll, array $optionIds): void
    {
        if (empty($optionIds)) {
            throw new InvalidSelectionException('At least one option must be selected.');
        }

        // Validate option IDs belong to this poll
        $validCount = $poll->options()->whereIn('id', $optionIds)->count();
        if ($validCount !== count($optionIds)) {
            throw new InvalidSelectionException('One or more selected options do not belong to this poll.');
        }

        // Validate single-choice polls have only one selection
        if (($poll->isSingleChoice() || $poll->isYesNo()) && count($optionIds) > 1) {
            throw new InvalidSelectionException('Only one option can be selected for this poll type.');
        }

        // Validate min/max selections for multiple choice
        if ($poll->isMultipleChoice() || $poll->isRanked()) {
            if ($poll->min_selections && count($optionIds) < $poll->min_selections) {
                throw new InvalidSelectionException("At least {$poll->min_selections} options must be selected.");
            }

            if ($poll->max_selections && count($optionIds) > $poll->max_selections) {
                throw new InvalidSelectionException("At most {$poll->max_selections} options can be selected.");
            }
        }
    }

    protected function fireEvent(string $key, mixed ...$args): void
    {
        $eventClass = config("larapoll.events.{$key}");

        if ($eventClass) {
            event(new $eventClass(...$args));
        }
    }

    protected function pollModel(): string
    {
        return config('larapoll.models.poll', Poll::class);
    }

    protected function optionModel(): string
    {
        return config('larapoll.models.option', PollOption::class);
    }

    protected function voteModel(): string
    {
        return config('larapoll.models.vote', PollVote::class);
    }
}
