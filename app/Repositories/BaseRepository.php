<?php

namespace App\Repositories;

use App\Contracts\BaseInterface;
use App\Exceptions\RepositoryException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

abstract class BaseRepository implements BaseInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->execute(
            fn () => $this->model->newQuery()->get(),
            'fetch all records',
        );
    }

    public function findById(int $id): ?Model
    {
        return $this->execute(
            fn () => $this->model->newQuery()->find($id),
            "fetch record with ID {$id}",
        );
    }

    public function create(array $data): Model
    {
        return $this->execute(
            fn () => $this->model->newQuery()->create($data),
            'create record',
        );
    }

    public function update(int $id, array $data): Model
    {
        return $this->execute(
            function () use ($id, $data) {
                $record = $this->model->newQuery()->findOrFail($id);
                $record->update($data);

                return $record->fresh();
            },
            "update record with ID {$id}",
        );
    }

    protected function execute(callable $callback, string $action): mixed
    {
        try {
            return $callback();
        } catch (ModelNotFoundException|ValidationException|AuthorizationException|HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("Repository failed to {$action}", [
                'model' => get_class($this->model),
                'exception' => $e,
            ]);

            throw new RepositoryException("Unable to {$action}", 500, $e);
        }
    }
}
