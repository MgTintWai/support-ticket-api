<?php

namespace App\Http\Resources;

use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Ticket */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $slaService = app(SlaService::class);

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'sla_deadline_at' => $this->sla_deadline_at?->toIso8601String(),
            'sla_status' => $slaService->resolveStatus($this->resource)->value,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'organization_id' => $this->organization_id,
            'created_by' => $this->created_by,
            'assigned_to' => $this->assigned_to,
            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'creator' => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),
            'assignee' => $this->whenLoaded('assignee', fn () => new UserResource($this->assignee)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
