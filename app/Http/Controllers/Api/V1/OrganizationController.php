<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Organization::class);

        $user = request()->user();

        if ($user->isAgent()) {
            $organizations = $this->organizationService->list();

            return ApiResponse::success(
                OrganizationResource::collection($organizations),
                'Organizations retrieved successfully.',
            );
        }

        $organization = $user->organization;
        abort_if($organization === null, Response::HTTP_NOT_FOUND);

        return ApiResponse::success(
            [new OrganizationResource($organization)],
            'Organization retrieved successfully.',
        );
    }

    public function store(OrganizationRequest $request): JsonResponse
    {
        $this->authorize('create', Organization::class);

        $organization = $this->organizationService->create($request->validated());

        return ApiResponse::created(
            new OrganizationResource($organization),
            'Organization created successfully.',
        );
    }

    public function show(int $organization): JsonResponse
    {
        $model = $this->organizationService->find($organization);
        abort_if($model === null, Response::HTTP_NOT_FOUND);

        $this->authorize('view', $model);

        return ApiResponse::success(
            new OrganizationResource($model),
            'Organization retrieved successfully.',
        );
    }

    public function update(OrganizationRequest $request, int $organization): JsonResponse
    {
        $existing = $this->organizationService->find($organization);
        abort_if($existing === null, Response::HTTP_NOT_FOUND);

        $this->authorize('update', $existing);

        $patch = $request->validated();
        $updated = $this->organizationService->update($organization, [
            'name' => $patch['name'] ?? $existing->name,
            'slug' => $patch['slug'] ?? $existing->slug,
            'is_active' => $patch['is_active'] ?? $existing->is_active,
        ]);

        return ApiResponse::success(
            new OrganizationResource($updated),
            'Organization updated successfully.',
        );
    }

    public function destroy(int $organization): JsonResponse
    {
        $existing = $this->organizationService->find($organization);
        abort_if($existing === null, Response::HTTP_NOT_FOUND);

        $this->authorize('delete', $existing);

        $this->organizationService->delete($organization);

        return ApiResponse::success(null, 'Organization deleted successfully.');
    }
}
