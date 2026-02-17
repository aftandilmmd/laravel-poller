<?php

namespace Aftandilmmd\PollVote\Events;

use Aftandilmmd\PollVote\Models\Poll;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PollActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Poll $poll,
    ) {}
}
