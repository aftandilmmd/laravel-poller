<?php

namespace Aftandilmmd\Poller\Http\Controllers\Api;

use Aftandilmmd\Poller\Http\Controllers\Api\Concerns\AuthorizesPollManagement;
use Aftandilmmd\Poller\Http\Resources\PollOptionResource;
use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
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

        $option = app('poller')->addOption($poll, $validated);

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

        $option = app('poller')->updateOption($option, $validated);

        return new PollOptionResource($option);
    }

    public function destroy(Poll $poll, PollOption $option): JsonResponse
    {
        $this->authorizeManagement($poll);
        $this->ensureOptionBelongsToPoll($poll, $option);

        app('poller')->removeOption($option);

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Poll $poll): JsonResponse
    {
        $this->authorizeManagement($poll);

        $validated = $request->validate([
            'option_ids' => 'required|array',
            'option_ids.*' => 'integer|exists:'.config('poller.tables.options', 'poll_vote_poll_options').',id',
        ]);

        app('poller')->reorderOptions($poll, $validated['option_ids']);

        return response()->json(['message' => __('poller::messages.options_reordered')]);
    }
}
