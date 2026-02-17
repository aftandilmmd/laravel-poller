<?php

namespace Aftandilmmd\PollVote\Http\Controllers\Api;

use Aftandilmmd\PollVote\Enums\PollStatus;
use Aftandilmmd\PollVote\Enums\PollType;
use Aftandilmmd\PollVote\Http\Controllers\Api\Concerns\AuthorizesPollManagement;
use Aftandilmmd\PollVote\Http\Resources\PollResource;
use Aftandilmmd\PollVote\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PollController extends Controller
{
    use AuthorizesPollManagement;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Poll::query()
            ->with(['options', 'creator'])
            ->withCount('votes');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->boolean('mine')) {
            $query->where('created_by', $request->user()->getAuthIdentifier());
        }

        if ($request->has('pollable_type') && $request->has('pollable_id')) {
            $query->where('pollable_type', $request->input('pollable_type'))
                ->where('pollable_id', $request->input('pollable_id'));
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%'.$request->input('search').'%');
        }

        return PollResource::collection(
            $query->latest()->paginate($request->input('per_page', config('poll-vote.pagination.polls', 20)))
        );
    }

    public function store(Request $request): PollResource
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:'.implode(',', PollType::values()),
            'status' => 'nullable|in:'.implode(',', PollStatus::values()),
            'pollable_type' => ['nullable', 'string', function ($attr, $value, $fail) {
                if ($value && ! class_exists($value)) {
                    $fail('The pollable type must be a valid model class.');
                }
            }],
            'pollable_id' => 'nullable|integer',
            'is_anonymous' => 'boolean',
            'show_results_before_close' => 'boolean',
            'allow_vote_change' => 'boolean',
            'allow_custom_options' => 'boolean',
            'max_custom_options' => 'nullable|integer|min:1',
            'requires_comment' => 'boolean',
            'max_votes_per_user' => 'nullable|integer|min:1',
            'min_selections' => 'nullable|integer|min:1',
            'max_selections' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'options' => 'nullable|array|min:2',
            'options.*.title' => 'required_with:options|string|max:255',
            'options.*.description' => 'nullable|string',
        ]);

        $service = app('poll-vote');
        $poll = $service->create($validated, $request->user());

        if (! empty($validated['options'])) {
            $service->addOptions($poll, collect($validated['options'])->map(fn ($option, $index) => array_merge($option, ['sort_order' => $index]))->toArray());
        }

        return new PollResource($poll->load('options'));
    }

    public function show(Poll $poll): PollResource
    {
        return new PollResource($poll->load(['options', 'creator'])->loadCount('votes'));
    }

    public function update(Request $request, Poll $poll): PollResource
    {
        $this->authorizeManagement($poll);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:'.implode(',', PollType::values()),
            'status' => 'in:'.implode(',', PollStatus::values()),
            'is_anonymous' => 'boolean',
            'show_results_before_close' => 'boolean',
            'allow_vote_change' => 'boolean',
            'allow_custom_options' => 'boolean',
            'max_custom_options' => 'nullable|integer|min:1',
            'requires_comment' => 'boolean',
            'max_votes_per_user' => 'nullable|integer|min:1',
            'min_selections' => 'nullable|integer|min:1',
            'max_selections' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $poll = app('poll-vote')->update($poll, $validated);

        return new PollResource($poll->load('options'));
    }

    public function destroy(Poll $poll): JsonResponse
    {
        $this->authorizeManagement($poll);

        app('poll-vote')->delete($poll);

        return response()->json(null, 204);
    }

    public function activate(Poll $poll): PollResource
    {
        $this->authorizeManagement($poll);

        return new PollResource(app('poll-vote')->activate($poll));
    }

    public function close(Poll $poll): PollResource
    {
        $this->authorizeManagement($poll);

        return new PollResource(app('poll-vote')->close($poll));
    }

    public function cancel(Poll $poll): PollResource
    {
        $this->authorizeManagement($poll);

        return new PollResource(app('poll-vote')->cancel($poll));
    }

    public function duplicate(Poll $poll): PollResource
    {
        $this->authorizeManagement($poll);

        return new PollResource(app('poll-vote')->duplicate($poll)->load('options'));
    }

    public function results(Poll $poll): JsonResponse
    {
        return response()->json(app('poll-vote')->getDetailedResults($poll));
    }
}
