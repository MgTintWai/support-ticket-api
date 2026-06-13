<?php

namespace App\Contracts;

use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationRepositoryInterface extends SoftDeletableRepositoryInterface
{
    public function findBySlug(string $slug): ?Organization;

    /**
     * @return Collection<int, Organization>
     */
    public function allActive(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function hasActiveTickets(int $organizationId): bool;
}
