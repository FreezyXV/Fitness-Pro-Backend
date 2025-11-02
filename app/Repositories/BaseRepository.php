<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Specify Model class name
     */
    abstract protected function model(): string;

    /**
     * Make Model instance
     */
    protected function makeModel(): Model
    {
        $model = app($this->model());

        if (!$model instanceof Model) {
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $model;
    }

    /**
     * Get all records
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Find a record by ID
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        return $model->fresh();
    }

    /**
     * Delete a record
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Find records matching criteria
     */
    public function findWhere(array $criteria): Collection
    {
        return $this->model->where($criteria)->get();
    }

    /**
     * Find first record matching criteria
     */
    public function findWhereFirst(array $criteria): ?Model
    {
        return $this->model->where($criteria)->first();
    }

    /**
     * Get a new query builder instance
     */
    protected function newQuery()
    {
        return $this->model->newQuery();
    }
}
