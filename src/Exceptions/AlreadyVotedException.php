<?php

namespace Aftandilmmd\Poller\Exceptions;

class AlreadyVotedException extends PollException
{
    public function __construct(string $message = 'You have already voted on this poll.')
    {
        parent::__construct($message);
    }
}
