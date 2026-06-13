<?php

namespace App\Contracts;

use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface CommentRepositoryInterface extends BaseInterface
{
    /**
     * @return Collection<int, TicketComment>
     */
    public function listForTicket(int $ticketId, User $user): Collection;

    public function createForTicket(int $ticketId, array $data): TicketComment;
}
