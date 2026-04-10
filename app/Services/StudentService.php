<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Student;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentService
{
    // Durasi cache dalam detik
    private const CACHE_TTL     = 60;

    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;

    // =========================================================================
    // Read
    // =========================================================================

    public function getAllStudents(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return Student::with('user', 'class')->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->paginate($perPage);
    }
    public function getStudentById(string $id): ?Student
    {
        $data = Cache::remember(
            "student.{$id}",
            self::CACHE_TTL,
            fn() => Student::with('user', 'class')->where('user_id', $id)->first()?->toArray()
        );

        return $data
            ? Student::hydrate([$data])->first()
            : null;
    }

    // =========================================================================
    // Write
    // =========================================================================

    public function createStudent(array $data)
    {
        $student = DB::transaction(function () use ($data) {
            $user = User::create([
                'username' => $data['username'],
                'full_name' => $data['full_name'],
                'password' => Hash::make($data['password']),
                'role' => 'student',

            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'nisn' => $data['nisn'],
                'class_id' => $data['class_id'] ?? null,
            ]);



            return $student;
        });

        return $student;
    }
    public function updateStudent(
        string $id,
        array $studentData,
    ): Student {
        // Ambil teacher + user relasi
        $student = Student::where('user_id', $id)->first();


        if (!$student) {
            throw new DataNotFound('Siswa tidak ditemukan');
        }

        DB::transaction(function () use ($student, $studentData) {
            // Update teacher table
            $student->update([
                'nisn' => $studentData['nisn'] ?? $student->nisn,
                'class_id' => $studentData['class_id'] ?? null,
            ]);

            // Update related user table
            if (isset($studentData['full_name']) || isset($studentData['username']) || isset($studentData['password'])) {
                $userData = [];
                if (isset($studentData['full_name'])) $userData['full_name'] = $studentData['full_name'];
                if (isset($studentData['username'])) $userData['username'] = $studentData['username'];
                if (isset($studentData['password'])) $userData['password'] = Hash::make($studentData['password']);
                User::where('id', $student->user_id)->update([
                    'full_name' => $userData['full_name'] ?? $student->user->full_name,
                    'username' => $userData['username'] ?? $student->user->username,
                    'password' => $userData['password'] ?? $student->user->password,
                    'role' => 'student'

                ]);
            }

            // Update pivot teacher_classes jika ada classIds
            // if (!empty($studentData['class_id'])) {
            //     $student->classes()->sync($studentData['class_id']);
            // }
        });

        // Hapus cache
        Cache::forget("student.{$id}");

        // Load user + classes untuk response
        return $student->load('user', 'class');
    }

    public function deleteStudent(string $id): Student
    {
        $student = Student::where('user_id', $id)->firstOrFail();

        DB::transaction(function () use ($student) {
            $student->user->delete(); // otomatis hapus teacher & pivot
        });

        Cache::forget("student.{$id}");

        return $student; // return object sebelum dihapus
    }
}
