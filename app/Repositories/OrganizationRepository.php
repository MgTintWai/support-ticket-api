<?php

namespace App\Repositories;

use App\Contracts\OrganizationRepositoryInterface;
use App\Models\Organization;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OrganizationRepository extends SoftDeletableRepository implements OrganizationRepositoryInterface
{
    public function __construct(Organization $organization)
    {
        parent::__construct($organization);
    }

    public function findBySlug(string $slug): ?Organization
    {
        return $this->execute(
            fn () => $this->model->newQuery()->where('slug', $slug)->first(),
            'find organization by slug',
        );
    }

    public function allActive(): Collection
    {
        return $this->execute(
            fn () => $this->model->newQuery()->where('is_active', true)->orderBy('name')->get(),
            'fetch active organizations',
        );
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->execute(
            fn () => $this->model->newQuery()->orderBy('name')->paginate($perPage),
            'paginate organizations',
        );
    }

    public function hasActiveTickets(int $organizationId): bool
    {
        return $this->execute(
            fn () => Ticket::query()->where('organization_id', $organizationId)->exists(),
            'check organization tickets',
        );
    }
}
