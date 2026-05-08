<?php

namespace Aftandilmmd\Poller\Exceptions;

class VoterRateLimitException extends PollException
{
    public function __construct(?string $message = null, int $code = 0, ?\Throwable $previous = null)
    {
        $message = $message ?: (function_exists('trans')
            ? trans('poller::messages.voter_rate_limit_exceeded')
            : 'You have reached the voting rate limit. Please try again later.');

        parent::__construct($message, $code, $previous);
    }
}
