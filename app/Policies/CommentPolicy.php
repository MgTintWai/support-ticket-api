<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user, Ticket $ticket): bool
    {
        return $user->isAgent() || $user->organization_id === $ticket->organization_id;
    }

    public function view(User $user, TicketComment $comment): bool
    {
        if ($user->isAgent()) {
            return true;
        }

        return ! $comment->is_internal
            && $user->organization_id === $comment->ticket->organization_id;
    }

    public function create(User $user, Ticket $ticket): bool
    {
        return $user->isAgent() || $user->organization_id === $ticket->organization_id;
    }
}
