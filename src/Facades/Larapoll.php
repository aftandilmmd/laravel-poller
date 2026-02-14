<?php

namespace Aftandilmmd\Larapoll\Facades;

use Aftandilmmd\Larapoll\Contracts\LarapollServiceInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aftandilmmd\Larapoll\Models\Poll create(array $attributes, \Illuminate\Contracts\Auth\Authenticatable $creator)
 * @method static \Aftandilmmd\Larapoll\Models\Poll update(\Aftandilmmd\Larapoll\Models\Poll $poll, array $attributes)
 * @method static bool delete(\Aftandilmmd\Larapoll\Models\Poll $poll)
 * @method static \Aftandilmmd\Larapoll\Models\Poll duplicate(\Aftandilmmd\Larapoll\Models\Poll $poll, ?array $overrides = null)
 * @method static \Aftandilmmd\Larapoll\Models\PollOption addOption(\Aftandilmmd\Larapoll\Models\Poll $poll, array $attributes)
 * @method static \Aftandilmmd\Larapoll\Models\PollOption updateOption(\Aftandilmmd\Larapoll\Models\PollOption $option, array $attributes)
 * @method static bool removeOption(\Aftandilmmd\Larapoll\Models\PollOption $option)
 * @method static void reorderOptions(\Aftandilmmd\Larapoll\Models\Poll $poll, array $optionIds)
 * @method static \Aftandilmmd\Larapoll\Models\Poll activate(\Aftandilmmd\Larapoll\Models\Poll $poll)
 * @method static \Aftandilmmd\Larapoll\Models\Poll close(\Aftandilmmd\Larapoll\Models\Poll $poll)
 * @method static \Aftandilmmd\Larapoll\Models\Poll cancel(\Aftandilmmd\Larapoll\Models\Poll $poll)
 * @method static \Illuminate\Support\Collection castVote(\Aftandilmmd\Larapoll\Models\Poll $poll, \Illuminate\Contracts\Auth\Authenticatable $voter, \Aftandilmmd\Larapoll\Models\PollOption|int|array $options, array $extra = [])
 * @method static \Illuminate\Support\Collection changeVote(\Aftandilmmd\Larapoll\Models\Poll $poll, \Illuminate\Contracts\Auth\Authenticatable $voter, \Aftandilmmd\Larapoll\Models\PollOption|int|array $options, array $extra = [])
 * @method static void retractVote(\Aftandilmmd\Larapoll\Models\Poll $poll, \Illuminate\Contracts\Auth\Authenticatable $voter)
 * @method static array getResults(\Aftandilmmd\Larapoll\Models\Poll $poll)
 * @method static array getDetailedResults(\Aftandilmmd\Larapoll\Models\Poll $poll)
 * @method static mixed exportResults(\Aftandilmmd\Larapoll\Models\Poll $poll, string $format = 'array')
 * @method static \Illuminate\Support\Collection getActivePolls(?\Illuminate\Database\Eloquent\Model $pollable = null)
 * @method static \Illuminate\Support\Collection getUserVotingHistory(\Illuminate\Contracts\Auth\Authenticatable $user, ?int $limit = null)
 *
 * @see \Aftandilmmd\Larapoll\Services\PollService
 */
class Larapoll extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LarapollServiceInterface::class;
    }
}
