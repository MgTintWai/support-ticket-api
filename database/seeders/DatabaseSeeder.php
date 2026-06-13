<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $acme = Organization::query()->create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'is_active' => true,
        ]);

        $globex = Organization::query()->create([
            'name' => 'Globex Inc',
            'slug' => 'globex-inc',
            'is_active' => true,
        ]);

        $agent = User::query()->create([
            'name' => 'Support Agent',
            'email' => 'agent@support.local',
            'password' => Hash::make('password'),
            'role' => UserRole::SupportAgent,
            'organization_id' => null,
        ]);

        $acmeClient = User::query()->create([
            'name' => 'Acme Client',
            'email' => 'client@acme.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Client,
            'organization_id' => $acme->id,
        ]);

        $globexClient = User::query()->create([
            'name' => 'Globex Client',
            'email' => 'client@globex.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Client,
            'organization_id' => $globex->id,
        ]);

        $slaService = app(SlaService::class);

        $acmeTicket = Ticket::query()->create([
            'organization_id' => $acme->id,
            'created_by' => $acmeClient->id,
            'assigned_to' => $agent->id,
            'subject' => 'Cannot access billing portal',
            'description' => 'Our finance team cannot log into the billing portal since this morning.',
            'status' => TicketStatus::InProgress,
            'priority' => TicketPriority::High,
            'sla_deadline_at' => $slaService->calculateDeadline(TicketPriority::High, now()),
        ]);

        $globexTicket = Ticket::query()->create([
            'organization_id' => $globex->id,
            'created_by' => $globexClient->id,
            'assigned_to' => null,
            'subject' => 'Request for API documentation',
            'description' => 'Please share the latest API documentation for our integration team.',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Medium,
            'sla_deadline_at' => $slaService->calculateDeadline(TicketPriority::Medium, now()),
        ]);

        TicketComment::query()->create([
            'ticket_id' => $acmeTicket->id,
            'user_id' => $acmeClient->id,
            'body' => 'This is blocking our month-end close.',
            'is_internal' => false,
        ]);

        TicketComment::query()->create([
            'ticket_id' => $acmeTicket->id,
            'user_id' => $agent->id,
            'body' => 'Escalated to infrastructure team.',
            'is_internal' => true,
        ]);

        TicketComment::query()->create([
            'ticket_id' => $globexTicket->id,
            'user_id' => $globexClient->id,
            'body' => 'We need this before Friday.',
            'is_internal' => false,
        ]);
    }
}
