<?php

namespace Aftandilmmd\PollVote\Events;

use Aftandilmmd\PollVote\Models\Poll;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PollCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Poll $poll,
        public Authenticatable $creator,
    ) {}
}
