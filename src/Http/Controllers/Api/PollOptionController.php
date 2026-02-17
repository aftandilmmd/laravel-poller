<?php

namespace Aftandilmmd\PollVote\Http\Controllers\Api;

use Aftandilmmd\PollVote\Http\Controllers\Api\Concerns\AuthorizesPollManagement;
use Aftandilmmd\PollVote\Http\Resources\PollOptionResource;
use Aftandilmmd\PollVote\Models\Poll;
use Aftandilmmd\PollVote\Models\PollOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PollOptionController extends Controller
{
    use AuthorizesPollManagement;

    public function store(Request $request, Poll $poll): PollOptionResource
    {
        $this->authorizeManagement($poll);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $option = app('poll-vote')->addOption($poll, $validated);

        return new PollOptionResource($option);
    }

    public function update(Request $request, Poll $poll, PollOption $option): PollOptionResource
    {
        $this->authorizeManagement($poll);
        $this->ensureOptionBelongsToPoll($poll, $option);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $option = app('poll-vote')->updateOption($option, $validated);

        return new PollOptionResource($option);
    }

    public function destroy(Poll $poll, PollOption $option): JsonResponse
    {
        $this->authorizeManagement($poll);
        $this->ensureOptionBelongsToPoll($poll, $option);

        app('poll-vote')->removeOption($option);

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Poll $poll): JsonResponse
    {
        $this->authorizeManagement($poll);

        $validated = $request->validate([
            'option_ids' => 'required|array',
            'option_ids.*' => 'integer|exists:'.config('poll-vote.tables.options', 'poll_vote_poll_options').',id',
        ]);

        app('poll-vote')->reorderOptions($poll, $validated['option_ids']);

        return response()->json(['message' => __('poll-vote::messages.options_reordered')]);
    }
}
