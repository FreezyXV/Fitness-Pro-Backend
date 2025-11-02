<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    /**
     * Get all records
     */
    public function all(): Collection;

    /**
     * Find a record by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record
     */
    public function update(Model $model, array $data): Model;

    /**
     * Delete a record
     */
    public function delete(Model $model): bool;

    /**
     * Find records matching criteria
     */
    public function findWhere(array $criteria): Collection;

    /**
     * Find first record matching criteria
     */
    public function findWhereFirst(array $criteria): ?Model;
}
