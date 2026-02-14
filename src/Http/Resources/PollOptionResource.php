<?php

namespace Aftandilmmd\Larapoll\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'poll_id' => $this->poll_id,
            'title' => $this->title,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'votes_count' => $this->votes_count,
            'is_custom' => $this->is_custom,
            'percentage' => $this->getPercentage(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
