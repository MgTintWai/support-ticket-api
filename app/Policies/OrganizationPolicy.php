<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->isAgent() || $user->organization_id === $organization->id;
    }

    public function create(User $user): bool
    {
        return $user->isAgent();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isAgent();
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->isAgent();
    }
}
