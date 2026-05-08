<?php

namespace Aftandilmmd\Poller\Events;

use Aftandilmmd\Poller\Events\Concerns\BroadcastsPollEvent;
use Aftandilmmd\Poller\Models\Poll;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteRetracted implements ShouldBroadcast
{
    use BroadcastsPollEvent, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Poll $poll,
        public Authenticatable $voter,
    ) {}

    protected function pollForBroadcast(): Poll
    {
        return $this->poll;
    }
}
