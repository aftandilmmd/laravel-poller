<?php

namespace Aftandilmmd\Larapoll\Http\Controllers\Api;

use Aftandilmmd\Larapoll\Exceptions\PollException;
use Aftandilmmd\Larapoll\Http\Resources\PollVoteResource;
use Aftandilmmd\Larapoll\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PollVoteController extends Controller
{
    public function index(Poll $poll): JsonResponse
    {
        if ($poll->is_anonymous) {
            return response()->json(['message' => __('larapoll::messages.votes_anonymous')], 403);
        }

        $votes = $poll->votes()
            ->with(['user', 'option'])
            ->latest()
            ->paginate(config('larapoll.pagination.votes', 50));

        return PollVoteResource::collection($votes)->response();
    }

    public function store(Request $request, Poll $poll): JsonResponse
    {
        $validated = $request->validate([
            'options' => 'required|array',
            'options.*' => 'integer',
            'comment' => 'nullable|string|max:5000',
            'rating' => 'nullable|integer|min:'.config('larapoll.rating.min', 1).'|max:'.config('larapoll.rating.max', 5),
            'ranks' => 'nullable|array',
            'ranks.*' => 'integer|min:1',
        ]);

        try {
            $votes = app('larapoll')->castVote(
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
            'rating' => 'nullable|integer|min:'.config('larapoll.rating.min', 1).'|max:'.config('larapoll.rating.max', 5),
            'ranks' => 'nullable|array',
            'ranks.*' => 'integer|min:1',
        ]);

        try {
            $votes = app('larapoll')->changeVote(
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
        } catch (PollException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, Poll $poll): JsonResponse
    {
        try {
            app('larapoll')->retractVote($poll, $request->user());

            return response()->json(null, 204);
        } catch (PollException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
