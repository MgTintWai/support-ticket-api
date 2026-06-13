<?php

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\SlaService;

beforeEach(function () {
    $organization = Organization::factory()->create();

    $this->client = User::factory()->create([
        'role' => UserRole::Client,
        'organization_id' => $organization->id,
    ]);

    $this->agent = User::factory()->agent()->create();

    $sla = app(SlaService::class);

    $this->ticket = Ticket::query()->create([
        'organization_id' => $organization->id,
        'created_by' => $this->client->id,
        'subject' => 'Help needed',
        'description' => 'Issue details',
        'priority' => TicketPriority::Medium,
        'sla_deadline_at' => $sla->calculateDeadline(TicketPriority::Medium, now()),
    ]);

    TicketComment::query()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->agent->id,
        'body' => 'Public agent reply',
        'is_internal' => false,
    ]);

    TicketComment::query()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->agent->id,
        'body' => 'Internal investigation note',
        'is_internal' => true,
    ]);
});

it('hides internal notes from clients', function () {
    $token = $this->client->createToken('api')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$this->ticket->id}/comments");

    $response->assertOk();

    $bodies = collect($response->json('data'))->pluck('body');

    expect($bodies)->toContain('Public agent reply')
        ->and($bodies)->not->toContain('Internal investigation note');

    foreach ($response->json('data') as $comment) {
        expect($comment['is_internal'])->toBeFalse();
    }
});

it('shows internal notes to support agents', function () {
    $token = $this->agent->createToken('api')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$this->ticket->id}/comments");

    $response->assertOk();

    $bodies = collect($response->json('data'))->pluck('body');

    expect($bodies)->toContain('Public agent reply')
        ->and($bodies)->toContain('Internal investigation note');
});

it('prevents clients from creating internal notes', function () {
    $token = $this->client->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/comments", [
            'body' => 'Attempted internal note',
            'is_internal' => true,
        ])
        ->assertCreated();

    $this->assertDatabaseMissing('ticket_comments', [
        'ticket_id' => $this->ticket->id,
        'body' => 'Attempted internal note',
        'is_internal' => true,
    ]);
});
