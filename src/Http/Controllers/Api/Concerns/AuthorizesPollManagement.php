<?php

namespace Aftandilmmd\Larapoll\Http\Controllers\Api\Concerns;

use Aftandilmmd\Larapoll\Models\Poll;
use Aftandilmmd\Larapoll\Models\PollOption;

trait AuthorizesPollManagement
{
    protected function authorizeManagement(Poll $poll): void
    {
        $user = auth()->user();

        if (method_exists($user, 'canManagePoll')) {
            if (! $user->canManagePoll($poll)) {
                abort(403, __('larapoll::messages.unauthorized'));
            }
        } elseif ($poll->created_by !== $user?->getAuthIdentifier()) {
            abort(403, __('larapoll::messages.unauthorized'));
        }
    }

    protected function ensureOptionBelongsToPoll(Poll $poll, PollOption $option): void
    {
        if ($option->poll_id !== $poll->id) {
            abort(404);
        }
    }
}
