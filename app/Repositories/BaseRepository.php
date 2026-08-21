<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected Model $model;

    /**
     * Find by ID.
     */
    public function find(int $id, array $relations = []): ?Model
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Find by UUID.
     */
    public function findByUuid(string $uuid, array $relations = []): ?Model
    {
        return $this->model->with($relations)->where('uuid', $uuid)->first();
    }

    /**
     * Get all records.
     */
    public function all(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Paginate records.
     */
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage);
    }

    /**
     * Create record.
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update record.
     */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        return $model->fresh();
    }

    /**
     * Delete record.
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Count records.
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->query();

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->count();
    }

    /**
     * Exists check.
     */
    public function exists(array $conditions): bool
    {
        $query = $this->model->query();

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->exists();
    }

    /**
     * Get model class name.
     */
    protected function getModelClass(): string
    {
        return class_basename($this->model);
    }
}
