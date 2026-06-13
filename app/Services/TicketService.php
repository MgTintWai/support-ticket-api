<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Ticket;
use App\Models\User;
use App\Contracts\TicketRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TicketService
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly UserRepositoryInterface $users,
        private readonly SlaService $slaService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(User $actor, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->tickets->paginateForUser($actor, $filters, $perPage);
    }

    public function find(int $id, User $actor): ?Ticket
    {
        return $this->tickets->findForUser($id, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Ticket
    {
        $priority = TicketPriority::from($data['priority']);
        $organizationId = $this->resolveOrganizationId($data, $actor);
        $createdAt = now();
        $deadline = $this->slaService->calculateDeadline($priority, $createdAt);

        return DB::transaction(fn () => $this->tickets->create([
            'organization_id' => $organizationId,
            'created_by' => $actor->id,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'status' => TicketStatus::Open->value,
            'priority' => $priority->value,
            'sla_deadline_at' => $deadline,
        ]))->load(['organization', 'creator', 'assignee']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $actor): Ticket
    {
        $ticket = $this->findOrFail($id, $actor);
        $priority = TicketPriority::from($data['priority']);

        $payload = [
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $priority->value,
        ];

        if ($ticket->priority !== $priority) {
            $payload['sla_deadline_at'] = $this->slaService
                ->calculateDeadline($priority, $ticket->created_at);
        }

        return DB::transaction(fn () => $this->tickets->update($id, $payload))
            ->load(['organization', 'creator', 'assignee']);
    }

    public function transitionStatus(int $id, TicketStatus $status, User $actor): Ticket
    {
        $ticket = $this->findOrFail($id, $actor);

        if (! $ticket->status->canTransitionTo($status)) {
            throw InvalidStatusTransitionException::from($ticket->status, $status);
        }

        $timestamps = [];

        if ($status === TicketStatus::Resolved) {
            $timestamps['resolved_at'] = now();
        }

        if ($status === TicketStatus::Closed) {
            $timestamps['closed_at'] = now();
            if ($ticket->resolved_at === null) {
                $timestamps['resolved_at'] = now();
            }
        }

        return DB::transaction(fn () => $this->tickets->updateStatus($id, $status, $timestamps));
    }

    public function assign(int $id, ?int $agentId, User $actor): Ticket
    {
        $this->findOrFail($id, $actor);

        if ($agentId !== null) {
            $agent = $this->users->findById($agentId);

            if (! $agent instanceof User || ! $agent->isAgent()) {
                throw ValidationException::withMessages([
                    'assigned_to' => ['The selected user must be a support agent.'],
                ]);
            }
        }

        return DB::transaction(fn () => $this->tickets->assign($id, $agentId));
    }

    public function delete(int $id, User $actor): bool
    {
        $ticket = $this->findOrFail($id, $actor);

        if ($ticket->status !== TicketStatus::Open) {
            throw ValidationException::withMessages([
                'ticket' => ['Only open tickets can be deleted.'],
            ]);
        }

        return DB::transaction(fn () => $this->tickets->delete($id));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveOrganizationId(array $data, User $actor): int
    {
        if ($actor->isClient()) {
            if ($actor->organization_id === null) {
                throw ValidationException::withMessages([
                    'organization_id' => ['Client user must belong to an organization.'],
                ]);
            }

            return $actor->organization_id;
        }

        if (empty($data['organization_id'])) {
            throw ValidationException::withMessages([
                'organization_id' => ['Organization is required when creating a ticket.'],
            ]);
        }

        return (int) $data['organization_id'];
    }

    private function findOrFail(int $id, User $actor): Ticket
    {
        $ticket = $this->find($id, $actor);
        abort_if($ticket === null, 404);

        return $ticket;
    }
}
