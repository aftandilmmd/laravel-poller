<?php

namespace Aftandilmmd\PollVote\Facades;

use Aftandilmmd\PollVote\Contracts\PollVoteServiceInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aftandilmmd\PollVote\Models\Poll create(array $attributes, \Illuminate\Contracts\Auth\Authenticatable $creator)
 * @method static \Aftandilmmd\PollVote\Models\Poll update(\Aftandilmmd\PollVote\Models\Poll $poll, array $attributes)
 * @method static bool delete(\Aftandilmmd\PollVote\Models\Poll $poll)
 * @method static \Aftandilmmd\PollVote\Models\Poll duplicate(\Aftandilmmd\PollVote\Models\Poll $poll, ?array $overrides = null)
 * @method static \Aftandilmmd\PollVote\Models\PollOption addOption(\Aftandilmmd\PollVote\Models\Poll $poll, array $attributes)
 * @method static \Aftandilmmd\PollVote\Models\PollOption updateOption(\Aftandilmmd\PollVote\Models\PollOption $option, array $attributes)
 * @method static bool removeOption(\Aftandilmmd\PollVote\Models\PollOption $option)
 * @method static void reorderOptions(\Aftandilmmd\PollVote\Models\Poll $poll, array $optionIds)
 * @method static \Aftandilmmd\PollVote\Models\Poll activate(\Aftandilmmd\PollVote\Models\Poll $poll)
 * @method static \Aftandilmmd\PollVote\Models\Poll close(\Aftandilmmd\PollVote\Models\Poll $poll)
 * @method static \Aftandilmmd\PollVote\Models\Poll cancel(\Aftandilmmd\PollVote\Models\Poll $poll)
 * @method static \Illuminate\Support\Collection castVote(\Aftandilmmd\PollVote\Models\Poll $poll, \Illuminate\Contracts\Auth\Authenticatable $voter, \Aftandilmmd\PollVote\Models\PollOption|int|array $options, array $extra = [])
 * @method static \Illuminate\Support\Collection changeVote(\Aftandilmmd\PollVote\Models\Poll $poll, \Illuminate\Contracts\Auth\Authenticatable $voter, \Aftandilmmd\PollVote\Models\PollOption|int|array $options, array $extra = [])
 * @method static void retractVote(\Aftandilmmd\PollVote\Models\Poll $poll, \Illuminate\Contracts\Auth\Authenticatable $voter)
 * @method static array getResults(\Aftandilmmd\PollVote\Models\Poll $poll)
 * @method static array getDetailedResults(\Aftandilmmd\PollVote\Models\Poll $poll)
 * @method static mixed exportResults(\Aftandilmmd\PollVote\Models\Poll $poll, string $format = 'array')
 * @method static \Illuminate\Support\Collection getActivePolls(?\Illuminate\Database\Eloquent\Model $pollable = null)
 * @method static \Illuminate\Support\Collection getUserVotingHistory(\Illuminate\Contracts\Auth\Authenticatable $user, ?int $limit = null)
 *
 * @see \Aftandilmmd\PollVote\Services\PollService
 */
class PollVote extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PollVoteServiceInterface::class;
    }
}
