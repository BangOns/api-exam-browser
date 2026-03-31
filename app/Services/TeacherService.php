<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherService
{
    // Durasi cache dalam detik
    private const CACHE_TTL     = 60;

    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;

    // Prefix cache untuk list — mudah di-flush sekaligus
    private const CACHE_LIST_PREFIX = 'teacher.list';

    // =========================================================================
    // Read
    // =========================================================================
    public function getAllTeachers(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $cacheKey = self::CACHE_LIST_PREFIX . ".{$search}.{$perPage}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage, $search) {
            return Teacher::when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                ->paginate($perPage);
        });
    }

    public function getTeacherById(string $id): ?Teacher
    {
        $data = Cache::remember(
            "teacher.{$id}",
            self::CACHE_TTL,
            fn() => Teacher::query()->where('user_id', $id)->first()?->toArray()
        );

        return $data
            ? Teacher::hydrate([$data])->first()
            : null;
    }

    // =========================================================================
    // Write
    // =========================================================================

    public function createTeacher(array $data): Teacher
    {
        $teacher = DB::transaction(function () use ($data) {
            $user = User::create([
                'username' => $data['username'],
                'full_name' => $data['full_name'],
                'password' => Hash::make($data['password']),
                'role' => 'teacher',
            ]);

            return Teacher::create([
                'user_id' => $user->id,
                'nip' => $data['nip'],
            ]);
        });

        $this->flushListCache();

        return $teacher;
    }
    public function updateTeacher(
        string $id,
        array $teacherData,
        ?array $classIds = null
    ): Teacher {
        // Ambil teacher + user relasi
        $teacher = Teacher::where('user_id', $id)->first();


        if (!$teacher) {
            throw new DataNotFound('Guru tidak ditemukan');
        }

        DB::transaction(function () use ($teacher, $teacherData, $classIds) {
            // Update teacher table
            $teacher->update([
                'nip' => $teacherData['nip'] ?? $teacher->nip,
            ]);

            // Update related user table
            if (isset($teacherData['full_name']) || isset($teacherData['username']) || isset($teacherData['password'])) {
                $userData = [];
                if (isset($teacherData['full_name'])) $userData['full_name'] = $teacherData['full_name'];
                if (isset($teacherData['username'])) $userData['username'] = $teacherData['username'];
                if (isset($teacherData['password'])) $userData['password'] = Hash::make($teacherData['password']);
                User::where('id', $teacher->user_id)->update([
                    'full_name' => $userData['full_name'] ?? $teacher->user->full_name,
                    'username' => $userData['username'] ?? $teacher->user->username,
                    'password' => $userData['password'] ?? $teacher->user->password,
                    'role' => 'teacher'
                ]);
            }

            // Update pivot teacher_classes jika ada classIds
            if (is_array($classIds)) {
                $teacher->classes()->sync($classIds);
            }
        });

        // Hapus cache
        Cache::forget("teacher.{$id}");
        $this->flushListCache();

        // Load user + classes untuk response
        return $teacher->load('user', 'classes');
    }

    public function deleteTeacher(string $id): Teacher
    {
        // Fetch langsung dari DB
        $teacher = Teacher::where('user_id', $id)->first();

        if (!$teacher) {
            throw new DataNotFound('Guru tidak ditemukan');
        }
        return DB::transaction(function () use ($teacher, $id) {
            $deleted = Teacher::where('user_id', $id)->delete();
            $user = User::where('id', $teacher->user_id)->delete();
            if ($deleted && $user) {
                Cache::forget("teacher.{$id}");
                $this->flushListCache();
            }

            return $deleted;
        });
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
