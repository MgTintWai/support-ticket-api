<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->execute(
            fn () => $this->model->newQuery()->where('email', $email)->first(),
            'find user by email',
        );
    }

    public function listAgents(): Collection
    {
        return $this->execute(
            fn () => $this->model->newQuery()
                ->where('role', UserRole::SupportAgent->value)
                ->orderBy('name')
                ->get(),
            'list support agents',
        );
    }
}
