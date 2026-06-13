<?php

namespace App\Services;

use App\Models\Organization;
use App\Contracts\OrganizationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OrganizationService
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization
    {
        $payload = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'is_active' => $data['is_active'] ?? true,
        ];

        return DB::transaction(fn () => $this->organizations->create($payload));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Organization
    {
        $existing = $this->find($id);
        abort_if($existing === null, 404);

        $payload = [
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? $existing->is_active,
        ];

        if (isset($data['slug'])) {
            $payload['slug'] = $data['slug'];
        }

        return DB::transaction(fn () => $this->organizations->update($id, $payload));
    }

    public function delete(int $id): bool
    {
        if ($this->organizations->hasActiveTickets($id)) {
            throw ValidationException::withMessages([
                'organization' => ['Cannot delete an organization that has tickets.'],
            ]);
        }

        return DB::transaction(fn () => $this->organizations->delete($id));
    }

    public function find(int $id): ?Organization
    {
        $row = $this->organizations->findById($id);

        return $row instanceof Organization ? $row : null;
    }

    /**
     * @return Collection<int, Organization>
     */
    public function list(): Collection
    {
        return $this->organizations->allActive();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->organizations->paginate($perPage);
    }
}
