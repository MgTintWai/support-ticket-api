<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->client = User::factory()->create([
        'role' => UserRole::Client,
        'organization_id' => $this->organization->id,
    ]);
    $this->agent = User::factory()->agent()->create();
});

it('creates a ticket for a client user', function () {
    $token = $this->client->createToken('api')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => 'Login issue',
        'description' => 'Unable to sign in to the portal.',
        'priority' => TicketPriority::High->value,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.subject', 'Login issue')
        ->assertJsonPath('data.organization_id', $this->organization->id);

    $this->assertDatabaseHas('tickets', [
        'subject' => 'Login issue',
        'organization_id' => $this->organization->id,
        'created_by' => $this->client->id,
    ]);
});

it('lists tickets with filters', function () {
    $sla = app(SlaService::class);

    Ticket::query()->create([
        'organization_id' => $this->organization->id,
        'created_by' => $this->client->id,
        'subject' => 'Open ticket',
        'description' => 'Details',
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::Low,
        'sla_deadline_at' => $sla->calculateDeadline(TicketPriority::Low, now()),
    ]);

    Ticket::query()->create([
        'organization_id' => $this->organization->id,
        'created_by' => $this->client->id,
        'subject' => 'Resolved ticket',
        'description' => 'Details',
        'status' => TicketStatus::Resolved,
        'priority' => TicketPriority::Medium,
        'sla_deadline_at' => $sla->calculateDeadline(TicketPriority::Medium, now()),
        'resolved_at' => now(),
    ]);

    $token = $this->agent->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/tickets?status=open')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');
});

it('creates a public comment on a ticket', function () {
    $sla = app(SlaService::class);

    $ticket = Ticket::query()->create([
        'organization_id' => $this->organization->id,
        'created_by' => $this->client->id,
        'subject' => 'Comment ticket',
        'description' => 'Details',
        'priority' => TicketPriority::Medium,
        'sla_deadline_at' => $sla->calculateDeadline(TicketPriority::Medium, now()),
    ]);

    $token = $this->client->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'Any update on this?',
        ])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Any update on this?')
        ->assertJsonPath('data.is_internal', false);
});

it('allows an agent to assign and update ticket status', function () {
    $sla = app(SlaService::class);

    $ticket = Ticket::query()->create([
        'organization_id' => $this->organization->id,
        'created_by' => $this->client->id,
        'subject' => 'Assignment ticket',
        'description' => 'Details',
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::Medium,
        'sla_deadline_at' => $sla->calculateDeadline(TicketPriority::Medium, now()),
    ]);

    $token = $this->agent->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}/assign", [
            'assigned_to' => $this->agent->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.assigned_to', $this->agent->id);

    $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}/status", [
            'status' => TicketStatus::InProgress->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', TicketStatus::InProgress->value);
});
