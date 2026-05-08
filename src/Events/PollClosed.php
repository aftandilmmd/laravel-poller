<?php

namespace Aftandilmmd\Poller\Events;

use Aftandilmmd\Poller\Models\Poll;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PollClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Poll $poll,
    ) {}
}
