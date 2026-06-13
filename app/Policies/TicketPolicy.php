<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAgent() || $user->organization_id === $ticket->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->isAgent() || $user->organization_id !== null;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isAgent()) {
            return true;
        }

        return $user->organization_id === $ticket->organization_id
            && $ticket->status === TicketStatus::Open;
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return $user->isAgent();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAgent();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAgent() && $ticket->status === TicketStatus::Open;
    }
}
