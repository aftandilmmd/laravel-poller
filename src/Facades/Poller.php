<?php

namespace Aftandilmmd\Poller\Facades;

use Aftandilmmd\Poller\Contracts\PollerServiceInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aftandilmmd\Poller\Models\Poll create(array $attributes, \Illuminate\Contracts\Auth\Authenticatable $creator)
 * @method static \Aftandilmmd\Poller\Models\Poll update(\Aftandilmmd\Poller\Models\Poll $poll, array $attributes)
 * @method static bool delete(\Aftandilmmd\Poller\Models\Poll $poll)
 * @method static \Aftandilmmd\Poller\Models\Poll duplicate(\Aftandilmmd\Poller\Models\Poll $poll, ?array $overrides = null)
 * @method static \Aftandilmmd\Poller\Models\PollOption addOption(\Aftandilmmd\Poller\Models\Poll $poll, array $attributes)
 * @method static \Aftandilmmd\Poller\Models\PollOption updateOption(\Aftandilmmd\Poller\Models\PollOption $option, array $attributes)
 * @method static bool removeOption(\Aftandilmmd\Poller\Models\PollOption $option)
 * @method static void reorderOptions(\Aftandilmmd\Poller\Models\Poll $poll, array $optionIds)
 * @method static \Aftandilmmd\Poller\Models\Poll activate(\Aftandilmmd\Poller\Models\Poll $poll)
 * @method static \Aftandilmmd\Poller\Models\Poll close(\Aftandilmmd\Poller\Models\Poll $poll)
 * @method static \Aftandilmmd\Poller\Models\Poll cancel(\Aftandilmmd\Poller\Models\Poll $poll)
 * @method static \Illuminate\Support\Collection castVote(\Aftandilmmd\Poller\Models\Poll $poll, \Illuminate\Contracts\Auth\Authenticatable $voter, \Aftandilmmd\Poller\Models\PollOption|int|array $options, array $extra = [])
 * @method static \Illuminate\Support\Collection changeVote(\Aftandilmmd\Poller\Models\Poll $poll, \Illuminate\Contracts\Auth\Authenticatable $voter, \Aftandilmmd\Poller\Models\PollOption|int|array $options, array $extra = [])
 * @method static void retractVote(\Aftandilmmd\Poller\Models\Poll $poll, \Illuminate\Contracts\Auth\Authenticatable $voter)
 * @method static array getResults(\Aftandilmmd\Poller\Models\Poll $poll)
 * @method static array getDetailedResults(\Aftandilmmd\Poller\Models\Poll $poll)
 * @method static mixed exportResults(\Aftandilmmd\Poller\Models\Poll $poll, string $format = 'array')
 * @method static \Illuminate\Support\Collection getActivePolls(?\Illuminate\Database\Eloquent\Model $pollable = null)
 * @method static \Illuminate\Support\Collection getUserVotingHistory(\Illuminate\Contracts\Auth\Authenticatable $user, ?int $limit = null)
 *
 * @see \Aftandilmmd\Poller\Services\PollService
 */
class Poller extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PollerServiceInterface::class;
    }
}
