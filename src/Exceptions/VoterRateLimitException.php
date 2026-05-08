<?php

namespace Aftandilmmd\Poller\Exceptions;

class VoterRateLimitException extends PollException
{
    protected $message = 'You have reached the voting rate limit. Please try again later.';
}
