<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketStatus;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexTicketRequest;
use App\Http\Requests\Api\V1\TicketAssignRequest;
use App\Http\Requests\Api\V1\TicketRequest;
use App\Http\Requests\Api\V1\TicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function index(IndexTicketRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $paginator = $this->ticketService->list(
            $request->user(),
            $request->validated(),
            (int) $request->input('per_page', 15),
        );

        return ApiResponse::paginated(
            $paginator,
            TicketResource::class,
            'Tickets retrieved successfully.',
        );
    }

    public function store(TicketRequest $request): JsonResponse
    {
        $this->authorize('create', Ticket::class);

        $ticket = $this->ticketService->create(
            $request->validated(),
            $request->user(),
        );

        return ApiResponse::created(
            new TicketResource($ticket),
            'Ticket created successfully.',
        );
    }

    public function show(int $ticket): JsonResponse
    {
        $model = $this->ticketService->find($ticket, request()->user());
        abort_if($model === null, Response::HTTP_NOT_FOUND);

        $this->authorize('view', $model);

        return ApiResponse::success(
            new TicketResource($model),
            'Ticket retrieved successfully.',
        );
    }

    public function update(TicketRequest $request, int $ticket): JsonResponse
    {
        $existing = $this->ticketService->find($ticket, $request->user());
        abort_if($existing === null, Response::HTTP_NOT_FOUND);

        $this->authorize('update', $existing);

        $patch = $request->validated();
        $updated = $this->ticketService->update($ticket, [
            'subject' => $patch['subject'] ?? $existing->subject,
            'description' => $patch['description'] ?? $existing->description,
            'priority' => $patch['priority'] ?? $existing->priority->value,
        ], $request->user());

        return ApiResponse::success(
            new TicketResource($updated),
            'Ticket updated successfully.',
        );
    }

    public function updateStatus(TicketStatusRequest $request, int $ticket): JsonResponse
    {
        $existing = $this->ticketService->find($ticket, $request->user());
        abort_if($existing === null, Response::HTTP_NOT_FOUND);

        $this->authorize('updateStatus', $existing);

        $updated = $this->ticketService->transitionStatus(
            $ticket,
            TicketStatus::from($request->validated('status')),
            $request->user(),
        );

        return ApiResponse::success(
            new TicketResource($updated),
            'Ticket status updated successfully.',
        );
    }

    public function assign(TicketAssignRequest $request, int $ticket): JsonResponse
    {
        $existing = $this->ticketService->find($ticket, $request->user());
        abort_if($existing === null, Response::HTTP_NOT_FOUND);

        $this->authorize('assign', $existing);

        $updated = $this->ticketService->assign(
            $ticket,
            $request->validated('assigned_to'),
            $request->user(),
        );

        return ApiResponse::success(
            new TicketResource($updated),
            'Ticket assigned successfully.',
        );
    }

    public function destroy(int $ticket): JsonResponse
    {
        $existing = $this->ticketService->find($ticket, request()->user());
        abort_if($existing === null, Response::HTTP_NOT_FOUND);

        $this->authorize('delete', $existing);

        $this->ticketService->delete($ticket, request()->user());

        return ApiResponse::success(null, 'Ticket deleted successfully.');
    }
}
