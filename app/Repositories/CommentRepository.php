<?php

namespace App\Repositories;

use App\Contracts\CommentRepositoryInterface;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CommentRepository extends BaseRepository implements CommentRepositoryInterface
{
    public function __construct(TicketComment $comment)
    {
        parent::__construct($comment);
    }

    public function listForTicket(int $ticketId, User $user): Collection
    {
        return $this->execute(
            function () use ($ticketId, $user) {
                $query = $this->model->newQuery()
                    ->with('author')
                    ->where('ticket_id', $ticketId)
                    ->orderBy('created_at');

                if ($user->isClient()) {
                    $query->where('is_internal', false);
                }

                return $query->get();
            },
            "list comments for ticket {$ticketId}",
        );
    }

    public function createForTicket(int $ticketId, array $data): TicketComment
    {
        return $this->execute(
            fn () => $this->model->newQuery()->create([
                'ticket_id' => $ticketId,
                ...$data,
            ])->load('author'),
            "create comment for ticket {$ticketId}",
        );
    }
}
