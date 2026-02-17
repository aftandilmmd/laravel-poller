<?php

namespace Aftandilmmd\PollVote\Exceptions;

class UnauthorizedVoteException extends PollException
{
    public function __construct(string $message = 'You are not authorized to vote on this poll.')
    {
        parent::__construct($message);
    }
}
