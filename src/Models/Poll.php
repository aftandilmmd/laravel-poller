<?php

namespace Aftandilmmd\Larapoll\Models;

use Aftandilmmd\Larapoll\Enums\PollStatus;
use Aftandilmmd\Larapoll\Enums\PollType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Poll extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): \Aftandilmmd\Larapoll\Database\Factories\PollFactory
    {
        return \Aftandilmmd\Larapoll\Database\Factories\PollFactory::new();
    }

    protected $fillable = [
        'pollable_type',
        'pollable_id',
        'created_by',
        'title',
        'description',
        'type',
        'status',
        'is_anonymous',
        'show_results_before_close',
        'allow_vote_change',
        'allow_custom_options',
        'max_custom_options',
        'requires_comment',
        'max_votes_per_user',
        'min_selections',
        'max_selections',
        'starts_at',
        'ends_at',
        'closed_at',
        'metadata',
    ];

    protected $attributes = [
        'allow_custom_options' => false,
    ];

    public function getTable(): string
    {
        return config('larapoll.tables.polls', 'larapoll_polls');
    }

    protected function casts(): array
    {
        return [
            'type' => PollType::class,
            'status' => PollStatus::class,
            'is_anonymous' => 'boolean',
            'show_results_before_close' => 'boolean',
            'allow_vote_change' => 'boolean',
            'allow_custom_options' => 'boolean',
            'requires_comment' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'closed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function pollable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('larapoll.user_model', \App\Models\User::class), 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(config('larapoll.models.option', PollOption::class))->orderBy('sort_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(config('larapoll.models.vote', PollVote::class));
    }

    // ── Scopes ─────────────────────────────────────────────────────

    #[Scope]
    protected function draft(Builder $query): void
    {
        $query->where('status', PollStatus::Draft);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', PollStatus::Active);
    }

    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->where('status', PollStatus::Closed);
    }

    #[Scope]
    protected function cancelled(Builder $query): void
    {
        $query->where('status', PollStatus::Cancelled);
    }

    #[Scope]
    protected function scheduled(Builder $query): void
    {
        $query->where('status', PollStatus::Draft)
            ->whereNotNull('starts_at')
            ->where('starts_at', '>', now());
    }

    #[Scope]
    protected function ended(Builder $query): void
    {
        $query->where('status', PollStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now());
    }

    // ── Status Helpers ─────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === PollStatus::Active;
    }

    public function isDraft(): bool
    {
        return $this->status === PollStatus::Draft;
    }

    public function isClosed(): bool
    {
        return $this->status === PollStatus::Closed;
    }

    public function isCancelled(): bool
    {
        return $this->status === PollStatus::Cancelled;
    }

    public function isScheduled(): bool
    {
        return $this->isDraft() && $this->starts_at && $this->starts_at->isFuture();
    }

    public function hasEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function isVotingOpen(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    // ── Lifecycle ──────────────────────────────────────────────────

    public function activate(): self
    {
        $this->update([
            'status' => PollStatus::Active,
            'starts_at' => $this->starts_at ?? now(),
        ]);

        $this->fireEvent('poll_activated', $this);

        return $this;
    }

    public function close(): self
    {
        $this->update([
            'status' => PollStatus::Closed,
            'closed_at' => now(),
        ]);

        $this->fireEvent('poll_closed', $this);

        return $this;
    }

    public function cancel(): self
    {
        $this->update([
            'status' => PollStatus::Cancelled,
        ]);

        $this->fireEvent('poll_cancelled', $this);

        return $this;
    }

    // ── Voting Helpers ─────────────────────────────────────────────

    public function getTotalVotes(): int
    {
        return $this->options->sum('votes_count');
    }

    public function getUniqueVoterCount(): int
    {
        return $this->votes()->distinct('user_id')->count('user_id');
    }

    public function getLeadingOption(): ?PollOption
    {
        return $this->options->sortByDesc('votes_count')->first();
    }

    /**
     * @return array<int, array{option_id: int, title: string, votes_count: int, percentage: float}>
     */
    public function getResultsAsPercentages(): array
    {
        $totalVotes = $this->getTotalVotes();

        return $this->options->map(fn (PollOption $option) => [
            'option_id' => $option->id,
            'title' => $option->title,
            'votes_count' => $option->votes_count,
            'percentage' => $totalVotes > 0
                ? round(($option->votes_count / $totalVotes) * 100, 1)
                : 0,
        ])->toArray();
    }

    public function hasUserVoted(Authenticatable $user): bool
    {
        return $this->votes()->where('user_id', $user->getAuthIdentifier())->exists();
    }

    public function getUserVotes(Authenticatable $user): Collection
    {
        return $this->votes()->where('user_id', $user->getAuthIdentifier())->get();
    }

    // ── Type Helpers ───────────────────────────────────────────────

    public function isYesNo(): bool
    {
        return $this->type === PollType::YesNo;
    }

    public function isSingleChoice(): bool
    {
        return $this->type === PollType::SingleChoice;
    }

    public function isMultipleChoice(): bool
    {
        return $this->type === PollType::MultipleChoice;
    }

    public function isRating(): bool
    {
        return $this->type === PollType::Rating;
    }

    public function isRanked(): bool
    {
        return $this->type === PollType::Ranked;
    }

    public function allowsMultipleSelections(): bool
    {
        return $this->isMultipleChoice() || $this->isRanked();
    }

    // ── Options ────────────────────────────────────────────────────

    public function reorderOptions(array $optionIds): self
    {
        app('larapoll')->reorderOptions($this, $optionIds);

        return $this;
    }

    // ── Clone ──────────────────────────────────────────────────────

    public function duplicate(?array $overrides = null): self
    {
        $attributes = array_merge(
            $this->only([
                'pollable_type',
                'pollable_id',
                'title',
                'description',
                'type',
                'is_anonymous',
                'show_results_before_close',
                'allow_vote_change',
                'allow_custom_options',
                'max_custom_options',
                'requires_comment',
                'max_votes_per_user',
                'min_selections',
                'max_selections',
                'metadata',
            ]),
            ['status' => PollStatus::Draft, 'created_by' => $this->created_by],
            $overrides ?? [],
        );

        /** @var self $newPoll */
        $newPoll = static::query()->create($attributes);

        $this->options->each(function (PollOption $option) use ($newPoll) {
            $newPoll->options()->create($option->only(['title', 'description', 'sort_order', 'metadata']));
        });

        return $newPoll->load('options');
    }

    // ── Custom Options ──────────────────────────────────────────────

    public function allowsCustomOptions(): bool
    {
        return $this->allow_custom_options && config('larapoll.features.custom_options', true);
    }

    public function customOptions(): HasMany
    {
        return $this->hasMany(config('larapoll.models.option', PollOption::class))->where('is_custom', true);
    }

    public function getCustomOptionCount(): int
    {
        return $this->customOptions()->count();
    }

    public function hasReachedCustomOptionLimit(): bool
    {
        if (! $this->max_custom_options) {
            return false;
        }

        return $this->getCustomOptionCount() >= $this->max_custom_options;
    }

    // ── Results Visibility ─────────────────────────────────────────

    public function canShowResults(?Authenticatable $user = null): bool
    {
        if ($this->isClosed() || $this->isCancelled()) {
            return true;
        }

        if ($this->show_results_before_close) {
            return true;
        }

        return false;
    }

    // ── Event Helper ──────────────────────────────────────────────

    protected function fireEvent(string $key, mixed ...$args): void
    {
        $eventClass = config("larapoll.events.{$key}");

        if ($eventClass) {
            event(new $eventClass(...$args));
        }
    }
}
