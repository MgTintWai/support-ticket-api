<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingOnClient = 'waiting_on_client';
    case Resolved = 'resolved';
    case Closed = 'closed';

    /**
     * @return array<TicketStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::InProgress, self::Resolved, self::Closed],
            self::InProgress => [self::WaitingOnClient, self::Resolved, self::Closed],
            self::WaitingOnClient => [self::InProgress, self::Resolved, self::Closed],
            self::Resolved => [self::Closed],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }
}
