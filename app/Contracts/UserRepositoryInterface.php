<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends BaseInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * @return Collection<int, User>
     */
    public function listAgents(): Collection;
}
