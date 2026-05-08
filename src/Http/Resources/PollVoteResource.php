<?php

namespace Aftandilmmd\Poller\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollVoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'poll_id' => $this->poll_id,
            'poll_option_id' => $this->poll_option_id,
            'user_id' => $this->user_id,
            'comment' => $this->comment,
            'rank' => $this->rank,
            'rating' => $this->rating,
            'option' => new PollOptionResource($this->whenLoaded('option')),
            'user' => $this->when($this->relationLoaded('user'), fn () => [
                'id' => $this->user?->getAuthIdentifier(),
                'name' => $this->user?->name ?? null,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
