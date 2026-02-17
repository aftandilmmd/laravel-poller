<?php

namespace Aftandilmmd\PollVote\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{
    use HasFactory;

    protected static function newFactory(): \Aftandilmmd\PollVote\Database\Factories\PollVoteFactory
    {
        return \Aftandilmmd\PollVote\Database\Factories\PollVoteFactory::new();
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
        return config('poll-vote.tables.votes', 'poll_vote_poll_votes');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(config('poll-vote.models.poll', Poll::class));
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(config('poll-vote.models.option', PollOption::class), 'poll_option_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('poll-vote.user_model', \App\Models\User::class));
    }
}
