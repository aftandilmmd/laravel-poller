<?php

namespace Aftandilmmd\Poller\Events\Concerns;

use Aftandilmmd\Poller\Models\Poll;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;

trait BroadcastsPollEvent
{
    abstract protected function pollForBroadcast(): Poll;

    /**
     * @return array<int, Channel|PrivateChannel|PresenceChannel>|Channel|PrivateChannel|PresenceChannel
     */
    public function broadcastOn(): mixed
    {
        $prefix = config('poller.broadcasting.channel_prefix', 'poller.poll');
        $name = "{$prefix}.{$this->pollForBroadcast()->getKey()}";

        return match (config('poller.broadcasting.channel', 'private')) {
            'presence' => new PresenceChannel($name),
            'public' => new Channel($name),
            default => new PrivateChannel($name),
        };
    }

    public function broadcastWhen(): bool
    {
        return (bool) config('poller.broadcasting.enabled', false);
    }
}
