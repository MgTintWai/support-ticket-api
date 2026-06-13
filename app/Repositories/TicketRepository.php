<?php

namespace App\Repositories;

use App\Contracts\TicketRepositoryInterface;
use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TicketRepository extends SoftDeletableRepository implements TicketRepositoryInterface
{
    public function __construct(Ticket $ticket)
    {
        parent::__construct($ticket);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->execute(
            fn () => $this->buildIndexQuery($user, $filters)->paginate($perPage),
            'paginate tickets for user',
        );
    }

    public function findForUser(int $id, User $user): ?Ticket
    {
        return $this->execute(
            function () use ($id, $user) {
                $query = $this->model->newQuery()
                    ->with(['organization', 'creator', 'assignee'])
                    ->whereKey($id);

                if ($user->isClient()) {
                    $query->where('organization_id', $user->organization_id);
                }

                return $query->first();
            },
            "find ticket {$id} for user",
        );
    }

    public function updateStatus(int $id, TicketStatus $status, array $timestamps = []): Ticket
    {
        return $this->execute(
            function () use ($id, $status, $timestamps) {
                $ticket = $this->model->newQuery()->findOrFail($id);
                $ticket->update(array_merge([
                    'status' => $status->value,
                ], $timestamps));

                return $ticket->fresh(['organization', 'creator', 'assignee']);
            },
            "update ticket {$id} status",
        );
    }

    public function assign(int $id, ?int $agentId): Ticket
    {
        return $this->execute(
            function () use ($id, $agentId) {
                $ticket = $this->model->newQuery()->findOrFail($id);
                $ticket->update(['assigned_to' => $agentId]);

                return $ticket->fresh(['organization', 'creator', 'assignee']);
            },
            "assign ticket {$id}",
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Ticket>
     */
    private function buildIndexQuery(User $user, array $filters): Builder
    {
        $query = $this->model->newQuery()
            ->with(['organization', 'creator', 'assignee']);

        if ($user->isClient()) {
            $query->where('organization_id', $user->organization_id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['organization_id']) && $user->isAgent()) {
            $query->where('organization_id', (int) $filters['organization_id']);
        }

        if (! empty($filters['unassigned']) && $user->isAgent()) {
            $query->whereNull('assigned_to');
        } elseif (! empty($filters['assigned_to']) && $user->isAgent()) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('subject', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if (! empty($filters['sla_status'])) {
            $this->applySlaStatusFilter($query, SlaStatus::from($filters['sla_status']));
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    private function applySlaStatusFilter(Builder $query, SlaStatus $slaStatus): void
    {
        $now = now();
        $terminalStatuses = [TicketStatus::Resolved->value, TicketStatus::Closed->value];

        match ($slaStatus) {
            SlaStatus::Overdue => $query
                ->where('sla_deadline_at', '<', $now)
                ->whereNotIn('status', $terminalStatuses),
            SlaStatus::DueSoon => $query
                ->where('sla_deadline_at', '>=', $now)
                ->whereNotIn('status', $terminalStatuses)
                ->where(function (Builder $builder) use ($now): void {
                    foreach (TicketPriority::cases() as $priority) {
                        $builder->orWhere(function (Builder $nested) use ($now, $priority): void {
                            $nested->where('priority', $priority->value)
                                ->where('sla_deadline_at', '<=', $now->copy()->addHours($priority->dueSoonHours()));
                        });
                    }
                }),
            SlaStatus::OnTrack => $query
                ->where('sla_deadline_at', '>', $now)
                ->whereNotIn('status', $terminalStatuses)
                ->where(function (Builder $builder) use ($now): void {
                    foreach (TicketPriority::cases() as $priority) {
                        $builder->orWhere(function (Builder $nested) use ($now, $priority): void {
                            $nested->where('priority', $priority->value)
                                ->where('sla_deadline_at', '>', $now->copy()->addHours($priority->dueSoonHours()));
                        });
                    }
                }),
            SlaStatus::Met => $query
                ->whereNotNull('resolved_at')
                ->whereColumn('resolved_at', '<=', 'sla_deadline_at'),
            SlaStatus::Breached => $query
                ->whereNotNull('resolved_at')
                ->whereColumn('resolved_at', '>', 'sla_deadline_at'),
        };
    }
}
