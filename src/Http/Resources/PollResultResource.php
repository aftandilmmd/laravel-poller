<?php

namespace Aftandilmmd\Poller\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'poll' => new PollResource($this['poll']),
            'total_votes' => $this['total_votes'],
            'unique_voters' => $this['unique_voters'],
            'options' => $this['options'],
            'leading_option' => $this['leading_option'] ? new PollOptionResource($this['leading_option']) : null,
        ];
    }
}
