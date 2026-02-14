<?php

namespace Aftandilmmd\Larapoll\Traits;

use Aftandilmmd\Larapoll\Models\Poll;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;

trait HasPolls
{
    public function polls(): MorphMany
    {
        return $this->morphMany(config('larapoll.models.poll', Poll::class), 'pollable');
    }

    public function activePolls(): MorphMany
    {
        return $this->polls()->active();
    }

    public function closedPolls(): MorphMany
    {
        return $this->polls()->closed();
    }

    public function draftPolls(): MorphMany
    {
        return $this->polls()->draft();
    }

    public function createPoll(array $attributes, ?Authenticatable $creator = null): Poll
    {
        $creator = $creator ?? auth()->user();

        return App::make('larapoll')->create(
            array_merge($attributes, [
                'pollable_type' => $this->getMorphClass(),
                'pollable_id' => $this->getKey(),
            ]),
            $creator,
        );
    }

    public function hasPollsInProgress(): bool
    {
        return $this->activePolls()->exists();
    }
}
