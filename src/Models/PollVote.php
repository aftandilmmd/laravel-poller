<?php

namespace Aftandilmmd\Larapoll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{
    use HasFactory;

    protected static function newFactory(): \Aftandilmmd\Larapoll\Database\Factories\PollVoteFactory
    {
        return \Aftandilmmd\Larapoll\Database\Factories\PollVoteFactory::new();
    }

    protected $fillable = [
        'poll_id',
        'poll_option_id',
        'user_id',
        'comment',
        'rank',
        'rating',
        'metadata',
    ];

    public function getTable(): string
    {
        return config('larapoll.tables.votes', 'larapoll_poll_votes');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(config('larapoll.models.poll', Poll::class));
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(config('larapoll.models.option', PollOption::class), 'poll_option_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('larapoll.user_model', \App\Models\User::class));
    }
}
