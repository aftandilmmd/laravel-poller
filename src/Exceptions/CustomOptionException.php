<?php

namespace Aftandilmmd\PollVote\Exceptions;

class CustomOptionException extends PollException
{
    public function __construct(string $message = 'Cannot add custom option to this poll.')
    {
        parent::__construct($message);
    }
}
