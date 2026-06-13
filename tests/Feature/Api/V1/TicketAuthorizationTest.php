<?php

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->acme = Organization::factory()->create(['name' => 'Acme']);
    $this->globex = Organization::factory()->create(['name' => 'Globex']);

    $this->acmeClient = User::factory()->create([
        'role' => UserRole::Client,
        'organization_id' => $this->acme->id,
        'email' => 'acme@client.local',
        'password' => Hash::make('password'),
    ]);

    $this->globexClient = User::factory()->create([
        'role' => UserRole::Client,
        'organization_id' => $this->globex->id,
        'email' => 'globex@client.local',
        'password' => Hash::make('password'),
    ]);

    $this->agent = User::factory()->agent()->create([
        'email' => 'agent@support.local',
        'password' => Hash::make('password'),
    ]);

    $sla = app(SlaService::class);

    $this->acmeTicket = Ticket::query()->create([
        'organization_id' => $this->acme->id,
        'created_by' => $this->acmeClient->id,
        'subject' => 'Acme issue',
        'description' => 'Acme only ticket',
        'priority' => TicketPriority::Medium,
        'sla_deadline_at' => $sla->calculateDeadline(TicketPriority::Medium, now()),
    ]);

    $this->globexTicket = Ticket::query()->create([
        'organization_id' => $this->globex->id,
        'created_by' => $this->globexClient->id,
        'subject' => 'Globex issue',
        'description' => 'Globex only ticket',
        'priority' => TicketPriority::Low,
        'sla_deadline_at' => $sla->calculateDeadline(TicketPriority::Low, now()),
    ]);
});

it('denies a client access to another organization ticket', function () {
    $token = $this->acmeClient->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/tickets/{$this->globexTicket->id}")
        ->assertNotFound();
});

it('allows a client to access their own organization ticket', function () {
    $token = $this->acmeClient->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/tickets/{$this->acmeTicket->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->acmeTicket->id);
});

it('allows an agent to access any organization ticket', function () {
    $token = $this->agent->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/tickets/{$this->globexTicket->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->globexTicket->id);
});

it('scopes ticket listing to the client organization', function () {
    $token = $this->acmeClient->createToken('api')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/tickets');

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($this->acmeTicket->id)
        ->and($ids)->not->toContain($this->globexTicket->id);
});
