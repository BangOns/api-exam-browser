<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Classes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ClassService
{
    // Durasi cache dalam detik
    private const CACHE_TTL     = 60;

    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;

    // Prefix cache untuk list — mudah di-flush sekaligus
    private const CACHE_LIST_PREFIX = 'class.list';

    // =========================================================================
    // Read
    // =========================================================================

    public function getAllClasses(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);


        return Classes::when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->paginate($perPage);
    }
    // public function getAllClasses(int $perPage = 5, string $search = ''): LengthAwarePaginator
    // {
    //     // Batasi perPage agar tidak bisa di-abuse
    //     $perPage = min($perPage, self::MAX_PER_PAGE);

    //     $cacheKey = self::CACHE_LIST_PREFIX . ".{$search}.{$perPage}";

    //     return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage, $search) {
    //         return Classes::when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
    //             ->paginate($perPage);
    //     });
    // }

    public function getClassById(string $id): ?Classes
    {
        $data = Cache::remember(
            "class.{$id}",
            self::CACHE_TTL,
            fn() => Classes::query()->find($id)?->toArray()
        );

        return $data
            ? Classes::hydrate([$data])->first()
            : null;
    }

    // =========================================================================
    // Write
    // =========================================================================

    public function createClass(array $data): Classes
    {
        $class = Classes::create($data);


        // Invalidate semua cache list agar data baru langsung muncul
        $this->flushListCache();

        return $class;
    }

    public function updateClass(string $id, array $data): bool
    {
        // Fetch langsung dari DB — jangan pakai cache untuk operasi write
        $class = Classes::find($id);

        if (!$class) {
            throw new DataNotFound('Kelas tidak ditemukan');
        }

        $updated = Classes::where('id', $id)->update($data);
        if ($updated) {
            // Hapus cache spesifik + semua list yang mungkin tampilkan data ini
            Cache::forget("class.{$id}");
            $this->flushListCache();
        }

        return $updated;
    }

    public function deleteClass(string $id): bool
    {
        // Fetch langsung dari DB
        $class = Classes::find($id);

        if (!$class) {
            throw new DataNotFound('Kelas tidak ditemukan');
        }

        $deleted = Classes::where('id', $id)->delete();
        if ($deleted) {
            Cache::forget("class.{$id}");
            $this->flushListCache();
        }

        return $deleted;
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Hapus semua cache list sekaligus menggunakan cache tags.
     * Jika driver tidak support tags (misal: file/database),
     * gunakan Cache::flush() atau ganti driver ke Redis/Memcached.
     */
    private function flushListCache(): void
    {
        // Jika pakai Redis / Memcached — gunakan tags (direkomendasikan)
        // Cache::tags([self::CACHE_LIST_PREFIX])->flush();

        // Jika pakai driver tanpa tags — flush seluruh cache
        // (pertimbangkan ganti ke Redis agar tidak flush semua data)
        Cache::flush();
    }
}
