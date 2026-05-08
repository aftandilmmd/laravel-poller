<?php

namespace Aftandilmmd\Poller\Events;

use Aftandilmmd\Poller\Models\Poll;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VoteCast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Poll $poll,
        public Authenticatable $voter,
        public Collection $votes,
    ) {}
}
