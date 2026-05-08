<?php

namespace Aftandilmmd\Poller\Exceptions;

class InvalidSelectionException extends PollException
{
    public function __construct(string $message = 'Invalid selection for this poll.')
    {
        parent::__construct($message);
    }
}
