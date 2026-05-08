<?php

namespace Aftandilmmd\Poller\Events;

use Aftandilmmd\Poller\Models\Poll;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VoteChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Poll $poll,
        public Authenticatable $voter,
        public Collection $oldVotes,
        public Collection $newVotes,
    ) {}
}
