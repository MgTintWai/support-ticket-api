<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Core persistence contract for aggregates without soft deletes.
 * Soft-delete behavior lives on {@see SoftDeletableRepositoryInterface}.
 */
interface BaseInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Model;

    public function create(array $data): Model;

    public function update(int $id, array $data): Model;
}
