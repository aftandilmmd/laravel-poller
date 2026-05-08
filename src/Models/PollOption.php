<?php

namespace Aftandilmmd\Poller\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    use HasFactory;

    protected static function newFactory(): \Aftandilmmd\Poller\Database\Factories\PollOptionFactory
    {
        return \Aftandilmmd\Poller\Database\Factories\PollOptionFactory::new();
    }

    protected $fillable = [
        'poll_id',
        'title',
        'description',
        'sort_order',
        'votes_count',
        'is_custom',
        'created_by',
        'metadata',
    ];

    protected $attributes = [
        'is_custom' => false,
    ];

    public function getTable(): string
    {
        return config('poller.tables.options', 'poller_poll_options');
    }

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(config('poller.models.poll', Poll::class));
    }

    public function votes(): HasMany
    {
        return $this->hasMany(config('poller.models.vote', PollVote::class));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('poller.user_model', \App\Models\User::class), 'created_by');
    }

    public function isCustom(): bool
    {
        return $this->is_custom;
    }

    public function getPercentage(): float
    {
        $totalVotes = $this->poll->getTotalVotes();

        return $totalVotes > 0
            ? round(($this->votes_count / $totalVotes) * 100, 1)
            : 0;
    }
}
