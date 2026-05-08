<?php

namespace Aftandilmmd\Poller\Support;

use Aftandilmmd\Poller\Models\Poll;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class ResultCache
{
    public static function enabled(): bool
    {
        return (bool) config('poller.cache.enabled', false);
    }

    public static function remember(Poll $poll, string $key, \Closure $callback): mixed
    {
        if (! self::enabled()) {
            return $callback();
        }

        return self::store()->remember(
            self::key($poll, $key),
            self::ttl(),
            $callback,
        );
    }

    public static function forget(Poll $poll): void
    {
        if (! self::enabled()) {
            return;
        }

        foreach (['results', 'detailed', 'total_votes', 'unique_voters', 'leading'] as $key) {
            self::store()->forget(self::key($poll, $key));
        }
    }

    public static function key(Poll $poll, string $key): string
    {
        $prefix = config('poller.cache.prefix', 'poller');

        return "{$prefix}:poll:{$poll->getKey()}:{$key}";
    }

    protected static function store(): Repository
    {
        return Cache::store(config('poller.cache.store'));
    }

    protected static function ttl(): int
    {
        return (int) config('poller.cache.ttl', 60);
    }
}
