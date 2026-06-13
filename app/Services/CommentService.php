<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Contracts\CommentRepositoryInterface;
use App\Contracts\TicketRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class CommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly TicketRepositoryInterface $tickets,
    ) {}

    /**
     * @return Collection<int, TicketComment>
     */
    public function list(int $ticketId, User $actor): Collection
    {
        $this->findAccessibleTicket($ticketId, $actor);

        return $this->comments->listForTicket($ticketId, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $ticketId, array $data, User $actor): TicketComment
    {
        $this->findAccessibleTicket($ticketId, $actor);

        $isInternal = $actor->isAgent() && ($data['is_internal'] ?? false);

        return DB::transaction(fn () => $this->comments->createForTicket($ticketId, [
            'user_id' => $actor->id,
            'body' => $data['body'],
            'is_internal' => $isInternal,
        ]));
    }

    private function findAccessibleTicket(int $ticketId, User $actor): Ticket
    {
        $ticket = $this->tickets->findForUser($ticketId, $actor);
        abort_if($ticket === null, 404);

        return $ticket;
    }
}
