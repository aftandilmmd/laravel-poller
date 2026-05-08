<?php

namespace Aftandilmmd\Poller\Contracts;

use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface PollerServiceInterface
{
    // ── CRUD ────────────────────────────────────────────────────────

    public function create(array $attributes, Authenticatable $creator): Poll;

    public function update(Poll $poll, array $attributes): Poll;

    public function delete(Poll $poll): bool;

    public function duplicate(Poll $poll, ?array $overrides = null): Poll;

    // ── Options ─────────────────────────────────────────────────────

    public function addOption(Poll $poll, array $attributes): PollOption;

    /**
     * @param  array<int, array{title: string, description?: string, sort_order?: int, metadata?: array}>  $options
     * @return \Illuminate\Support\Collection<int, PollOption>
     */
    public function addOptions(Poll $poll, array $options): Collection;

    public function updateOption(PollOption $option, array $attributes): PollOption;

    public function removeOption(PollOption $option): bool;

    /**
     * @param  array<int>  $optionIds
     */
    public function reorderOptions(Poll $poll, array $optionIds): void;

    public function addCustomOption(Poll $poll, Authenticatable $user, array $attributes): PollOption;

    // ── Lifecycle ───────────────────────────────────────────────────

    public function activate(Poll $poll): Poll;

    public function close(Poll $poll): Poll;

    public function cancel(Poll $poll): Poll;

    // ── Voting ──────────────────────────────────────────────────────

    /**
     * @param  PollOption|int|array<int|PollOption>  $options
     * @param  array{comment?: string, rank?: int, rating?: int, metadata?: array<string, mixed>}  $extra
     */
    public function castVote(Poll $poll, Authenticatable $voter, PollOption|int|array $options, array $extra = []): Collection;

    /**
     * @param  PollOption|int|array<int|PollOption>  $options
     * @param  array{comment?: string, rank?: int, rating?: int, metadata?: array<string, mixed>}  $extra
     */
    public function changeVote(Poll $poll, Authenticatable $voter, PollOption|int|array $options, array $extra = []): Collection;

    public function retractVote(Poll $poll, Authenticatable $voter): void;

    // ── Results ─────────────────────────────────────────────────────

    /**
     * @return array<int, array{option_id: int, title: string, votes_count: int, percentage: float}>
     */
    public function getResults(Poll $poll): array;

    /**
     * @return array{poll: Poll, total_votes: int, unique_voters: int, options: array, leading_option: ?PollOption}
     */
    public function getDetailedResults(Poll $poll): array;

    public function exportResults(Poll $poll, string $format = 'array'): mixed;

    // ── Queries ─────────────────────────────────────────────────────

    public function getActivePolls(?Model $pollable = null): Collection;

    public function getUserVotingHistory(Authenticatable $user, ?int $limit = null): Collection;
}
