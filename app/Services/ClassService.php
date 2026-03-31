<?php

namespace App\Services;

use App\Models\Classes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ClassService
{
    /**
     * Create a new class instance.
     */
    public function getAllClasses(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        return Cache::remember("class.list.{$search}.{$perPage}", 60, function () use ($perPage, $search) {
            $query = Classes::when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
            return $query->paginate($perPage);
        });
    }

    public function getClassById(string $id): ?Classes
    {
        return Cache::remember("class.{$id}", 60, function () use ($id) {
            return Classes::find($id);
        });
    }

    public function createClass(array $data): Classes
    {

        $class = Classes::create([
            'name' => $data['name'],
            'level' => $data['level'],
            'department' => $data['department'],
        ]);
        return $class;
    }

    public function updateClass(string $id, array $data): bool
    {
        $class = $this->getClassById($id);
        if (!$class) {
            return false;
        }
        return $class->update($data);
    }

    public function deleteClass(string $id): bool
    {
        $class = $this->getClassById($id);
        if (!$class) {
            return false;
        }
        return $class->delete();
    }
}
