<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $priority = fake()->randomElement(TicketPriority::cases());
        $createdAt = now();
        $slaService = app(SlaService::class);

        return [
            'organization_id' => Organization::factory(),
            'created_by' => User::factory()->client(),
            'assigned_to' => null,
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::Open,
            'priority' => $priority,
            'sla_deadline_at' => $slaService->calculateDeadline($priority, $createdAt),
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }
}
