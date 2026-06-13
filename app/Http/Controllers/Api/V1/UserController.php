<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function agents(): JsonResponse
    {
        abort_unless(request()->user()?->isAgent(), Response::HTTP_FORBIDDEN);

        return ApiResponse::success(
            UserResource::collection($this->users->listAgents()),
            'Support agents retrieved successfully.',
        );
    }
}
