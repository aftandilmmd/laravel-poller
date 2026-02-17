<?php

namespace Aftandilmmd\PollVote\Exceptions;

class PollClosedException extends PollException
{
    public function __construct(string $message = 'This poll is not currently accepting votes.')
    {
        parent::__construct($message);
    }
}
