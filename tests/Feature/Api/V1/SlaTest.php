<?php

use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Carbon\Carbon;

it('calculates high priority SLA as 24 hours from creation', function () {
    $service = app(SlaService::class);
    $createdAt = Carbon::parse('2026-06-12 10:00:00');

    $deadline = $service->calculateDeadline(TicketPriority::High, $createdAt);

    expect($deadline->toDateTimeString())->toBe('2026-06-13 10:00:00');
});

it('marks active tickets as due soon within the threshold', function () {
    $service = app(SlaService::class);
    $organization = Organization::factory()->create();
    $client = User::factory()->client($organization)->create();

    $ticket = Ticket::query()->create([
        'organization_id' => $organization->id,
        'created_by' => $client->id,
        'subject' => 'Due soon ticket',
        'description' => 'Testing SLA',
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::High,
        'sla_deadline_at' => now()->addHours(4),
    ]);

    expect($service->resolveStatus($ticket))->toBe(SlaStatus::DueSoon);
});

it('marks unresolved tickets as overdue after the deadline', function () {
    $service = app(SlaService::class);
    $organization = Organization::factory()->create();
    $client = User::factory()->client($organization)->create();

    $ticket = Ticket::query()->create([
        'organization_id' => $organization->id,
        'created_by' => $client->id,
        'subject' => 'Overdue ticket',
        'description' => 'Testing SLA',
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::Medium,
        'sla_deadline_at' => now()->subHour(),
    ]);

    expect($service->resolveStatus($ticket))->toBe(SlaStatus::Overdue);
});

it('exposes sla_status on ticket API responses', function () {
    $organization = Organization::factory()->create();
    $client = User::factory()->client($organization)->create();
    $sla = app(SlaService::class);

    $ticket = Ticket::query()->create([
        'organization_id' => $organization->id,
        'created_by' => $client->id,
        'subject' => 'API SLA ticket',
        'description' => 'Testing SLA response',
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::High,
        'sla_deadline_at' => $sla->calculateDeadline(TicketPriority::High, now()),
    ]);

    $token = $client->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.sla_status', SlaStatus::OnTrack->value);
});
