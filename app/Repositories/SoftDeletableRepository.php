<?php

namespace App\Repositories;

use App\Contracts\SoftDeletableRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Base repository for models using {@see \Illuminate\Database\Eloquent\SoftDeletes}.
 */
abstract class SoftDeletableRepository extends BaseRepository implements SoftDeletableRepositoryInterface
{
    public function delete(int $id): bool
    {
        return $this->execute(
            fn () => (bool) $this->model->newQuery()->findOrFail($id)->delete(),
            "soft delete record with ID {$id}",
        );
    }

    public function restore(int $id): Model
    {
        return $this->execute(
            function () use ($id) {
                $record = $this->model->newQuery()->withTrashed()->findOrFail($id);
                $record->restore();

                return $record->fresh();
            },
            "restore record with ID {$id}",
        );
    }

    public function forceDelete(int $id): bool
    {
        return $this->execute(
            fn () => (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete(),
            "force delete record with ID {$id}",
        );
    }
}
