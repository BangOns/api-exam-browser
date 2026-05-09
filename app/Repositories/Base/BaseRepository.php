<?php

namespace App\Repositories\Base;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    protected int $maxPerPage = 100;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all queryable relations needed for repository
     */
    abstract protected function getWithRelations();

    /**
     * Get all selectable columns
     */
    abstract protected function getSelectableColumns(): array;

    /**
     * Get search columns
     */
    abstract protected function getSearchColumns(): array;

    public function paginate(int $perPage = 15, string $search = ''): LengthAwarePaginator
    {
        $perPage = min($perPage, $this->maxPerPage);

        $query = $this->model
            ->select($this->getSelectableColumns())
            ->with($this->getWithRelations());

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                foreach ($this->getSearchColumns() as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        return $query->paginate($perPage);
    }

    public function find(string $id)
    {
        return $this->model
            ->select($this->getSelectableColumns())
            ->with($this->getWithRelations())
            ->where('id', $id)
            ->firstOrFail();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data)
    {
        $model = $this->model->findOrFail($id);
        $model->update($data);
        return $model->refresh();
    }

    public function delete(string $id): bool
    {
        return $this->model->destroy($id) > 0;
    }

    public function all()
    {
        return $this->model
            ->select($this->getSelectableColumns())
            ->get();
    }
}
