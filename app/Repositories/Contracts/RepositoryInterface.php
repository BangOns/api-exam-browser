<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    /**
     * Get all records with pagination
     */
    public function paginate(int $perPage = 15, string $search = ''): LengthAwarePaginator;

    /**
     * Get record by ID
     */
    public function find(string $id);

    /**
     * Create a new record
     */
    public function create(array $data);

    /**
     * Update an existing record
     */
    public function update(string $id, array $data);

    /**
     * Delete a record
     */
    public function delete(string $id): bool;

    /**
     * Get all records without pagination
     */
    public function all();
}
