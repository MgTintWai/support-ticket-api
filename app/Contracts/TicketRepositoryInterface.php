<?php

namespace App\Contracts;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TicketRepositoryInterface extends SoftDeletableRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator;

    public function findForUser(int $id, User $user): ?Ticket;

    public function updateStatus(int $id, TicketStatus $status, array $timestamps = []): Ticket;

    public function assign(int $id, ?int $agentId): Ticket;
}
