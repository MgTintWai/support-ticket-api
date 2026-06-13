<?php

namespace App\Services;

use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Carbon\CarbonInterface;

final class SlaService
{
    public function calculateDeadline(TicketPriority $priority, CarbonInterface $createdAt): CarbonInterface
    {
        return $createdAt->copy()->addHours($priority->slaHours());
    }

    public function resolveStatus(Ticket $ticket, ?CarbonInterface $now = null): SlaStatus
    {
        $now ??= now();

        if (in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            $resolvedAt = $ticket->resolved_at ?? $ticket->closed_at ?? $now;

            return $resolvedAt->lte($ticket->sla_deadline_at)
                ? SlaStatus::Met
                : SlaStatus::Breached;
        }

        if ($now->gt($ticket->sla_deadline_at)) {
            return SlaStatus::Overdue;
        }

        $hoursRemaining = $now->diffInHours($ticket->sla_deadline_at, false);

        if ($hoursRemaining <= $ticket->priority->dueSoonHours()) {
            return SlaStatus::DueSoon;
        }

        return SlaStatus::OnTrack;
    }
}
