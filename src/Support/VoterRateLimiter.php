<?php

namespace Aftandilmmd\Poller\Support;

use Aftandilmmd\Poller\Exceptions\VoterRateLimitException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\RateLimiter;

class VoterRateLimiter
{
    public static function enabled(): bool
    {
        return (bool) config('poller.voter_rate_limit.enabled', false);
    }

    public static function check(Authenticatable $voter): void
    {
        if (! self::enabled()) {
            return;
        }

        $key = self::key($voter);
        $max = (int) config('poller.voter_rate_limit.max_votes', 30);
        $decay = (int) config('poller.voter_rate_limit.per_minutes', 60) * 60;

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new VoterRateLimitException;
        }

        RateLimiter::hit($key, $decay);
    }

    public static function clear(Authenticatable $voter): void
    {
        RateLimiter::clear(self::key($voter));
    }

    public static function key(Authenticatable $voter): string
    {
        return 'poller:voter:'.$voter->getAuthIdentifier();
    }
}
