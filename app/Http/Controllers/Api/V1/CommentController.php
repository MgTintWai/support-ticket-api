<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CommentRequest;
use App\Http\Resources\CommentResource;
use App\Services\CommentService;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService,
        private readonly TicketService $ticketService,
    ) {}

    public function index(int $ticket): JsonResponse
    {
        $ticketModel = $this->ticketService->find($ticket, request()->user());
        abort_if($ticketModel === null, Response::HTTP_NOT_FOUND);

        $this->authorize('view', $ticketModel);

        $comments = $this->commentService->list($ticket, request()->user());

        return ApiResponse::success(
            CommentResource::collection($comments),
            'Comments retrieved successfully.',
        );
    }

    public function store(CommentRequest $request, int $ticket): JsonResponse
    {
        $ticketModel = $this->ticketService->find($ticket, $request->user());
        abort_if($ticketModel === null, Response::HTTP_NOT_FOUND);

        $this->authorize('view', $ticketModel);

        $comment = $this->commentService->create(
            $ticket,
            $request->validated(),
            $request->user(),
        );

        return ApiResponse::created(
            new CommentResource($comment),
            'Comment created successfully.',
        );
    }
}
