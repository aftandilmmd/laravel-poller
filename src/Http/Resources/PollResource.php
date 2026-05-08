<?php

namespace Aftandilmmd\Poller\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_anonymous' => $this->is_anonymous,
            'show_results_before_close' => $this->show_results_before_close,
            'allow_vote_change' => $this->allow_vote_change,
            'allow_custom_options' => $this->allow_custom_options,
            'requires_comment' => $this->requires_comment,
            'max_votes_per_user' => $this->max_votes_per_user,
            'min_selections' => $this->min_selections,
            'max_selections' => $this->max_selections,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'is_voting_open' => $this->isVotingOpen(),
            'total_votes' => $this->getTotalVotes(),
            'options' => PollOptionResource::collection($this->whenLoaded('options')),
            'votes_count' => $this->whenCounted('votes'),
            'creator' => $this->when(! $this->is_anonymous, fn () => [
                'id' => $this->creator?->getAuthIdentifier(),
                'name' => $this->creator?->name ?? null,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
