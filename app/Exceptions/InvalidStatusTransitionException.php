<?php

namespace App\Exceptions;

use App\Enums\TicketStatus;
use Exception;

class InvalidStatusTransitionException extends Exception
{
    public static function from(TicketStatus $from, TicketStatus $to): self
    {
        return new self(
            "Cannot transition ticket from {$from->value} to {$to->value}.",
            422,
        );
    }
}
