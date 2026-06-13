<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Extends core CRUD with soft-delete restore and permanent removal.
 * Implementations should use models that use {@see \Illuminate\Database\Eloquent\SoftDeletes}.
 */
interface SoftDeletableRepositoryInterface extends BaseInterface
{
    public function delete(int $id): bool;

    public function restore(int $id): Model;

    public function forceDelete(int $id): bool;
}
