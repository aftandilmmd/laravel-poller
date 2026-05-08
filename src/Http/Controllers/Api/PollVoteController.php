<?php

namespace Aftandilmmd\Poller\Http\Controllers\Api;

use Aftandilmmd\Poller\Exceptions\PollException;
use Aftandilmmd\Poller\Exceptions\VoterRateLimitException;
use Aftandilmmd\Poller\Http\Resources\PollVoteResource;
use Aftandilmmd\Poller\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PollVoteController extends Controller
{
    public function index(Poll $poll): JsonResponse
    {
        if ($poll->is_anonymous) {
            return response()->json(['message' => __('poller::messages.votes_anonymous')], 403);
        }

        $votes = $poll->votes()
            ->with(['user', 'option'])
            ->latest()
            ->paginate(config('poller.pagination.votes', 50));

        return PollVoteResource::collection($votes)->response();
    }

    public function store(Request $request, Poll $poll): JsonResponse
    {
        $validated = $request->validate([
            'options' => 'required|array',
            'options.*' => 'integer',
            'comment' => 'nullable|string|max:5000',
            'rating' => 'nullable|integer|min:'.config('poller.rating.min', 1).'|max:'.config('poller.rating.max', 5),
            'ranks' => 'nullable|array',
            'ranks.*' => 'integer|min:1',
        ]);

        try {
            $votes = app('poller')->castVote(
                $poll,
                $request->user(),
                $validated['options'],
                array_filter([
                    'comment' => $validated['comment'] ?? null,
                    'rating' => $validated['rating'] ?? null,
                    'ranks' => $validated['ranks'] ?? null,
                ]),
            );

            return PollVoteResource::collection($votes)->response()->setStatusCode(201);
        } catch (VoterRateLimitException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        } catch (PollException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, Poll $poll): JsonResponse
    {
        $validated = $request->validate([
            'options' => 'required|array',
            'options.*' => 'integer',
            'comment' => 'nullable|string|max:5000',
            'rating' => 'nullable|integer|min:'.config('poller.rating.min', 1).'|max:'.config('poller.rating.max', 5),
            'ranks' => 'nullable|array',
            'ranks.*' => 'integer|min:1',
        ]);

        try {
            $votes = app('poller')->changeVote(
                $poll,
                $request->user(),
                $validated['options'],
                array_filter([
                    'comment' => $validated['comment'] ?? null,
                    'rating' => $validated['rating'] ?? null,
                    'ranks' => $validated['ranks'] ?? null,
                ]),
            );

            return PollVoteResource::collection($votes)->response();
        } catch (VoterRateLimitException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        } catch (PollException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, Poll $poll): JsonResponse
    {
        try {
            app('poller')->retractVote($poll, $request->user());

            return response()->json(null, 204);
        } catch (PollException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
