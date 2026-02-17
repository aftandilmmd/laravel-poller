<?php

namespace Aftandilmmd\PollVote\Http\Controllers\Api\Concerns;

use Aftandilmmd\PollVote\Models\Poll;
use Aftandilmmd\PollVote\Models\PollOption;

trait AuthorizesPollManagement
{
    protected function authorizeManagement(Poll $poll): void
    {
        $user = auth()->user();

        if (method_exists($user, 'canManagePoll')) {
            if (! $user->canManagePoll($poll)) {
                abort(403, __('poll-vote::messages.unauthorized'));
            }
        } elseif ($poll->created_by !== $user?->getAuthIdentifier()) {
            abort(403, __('poll-vote::messages.unauthorized'));
        }
    }

    protected function ensureOptionBelongsToPoll(Poll $poll, PollOption $option): void
    {
        if ($option->poll_id !== $poll->id) {
            abort(404);
        }
    }
}
