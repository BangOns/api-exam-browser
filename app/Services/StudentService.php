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
    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;

    // =========================================================================
    // Read
    // =========================================================================

    public function getAllStudents(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return Student::select('id', 'user_id', 'class_id', 'nisn', 'status', 'created_at', 'updated_at')
            ->with('user:id,username,full_name,role', 'class:id,name')
            ->when($search, fn($q) => $q->where('nisn', 'like', "%{$search}%")
                ->orWhereHas('user', fn($uq) => $uq->where('full_name', 'like', "%{$search}%")))
            ->paginate($perPage);
    }
    public function getStudentById(string $id): ?Student
    {
        return Student::where('id', $id)
            ->select('id', 'user_id', 'class_id', 'nisn', 'created_at', 'updated_at')
            ->with('user:id,username,full_name,role', 'class:id,name')
            ->first();
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
        $student = Student::where('id', $id)->first();

        if (!$student) {
            throw new DataNotFound('Siswa tidak ditemukan');
        }

        DB::transaction(function () use ($student, $studentData) {
            // Update student attributes
            $student->update([
                'nisn' => $studentData['nisn'] ?? $student->nisn,
                'class_id' => $studentData['class_id'] ?? $student->class_id,
            ]);

            // Update related user if provided
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

            // $student->fresh('user');
            // return $student;

            // Update pivot teacher_classes jika ada classIds
            // if (!empty($studentData['class_id'])) {
            //     $student->classes()->sync($studentData['class_id']);
            // }
        });

        // Clear cache
        Cache::forget("student.{$id}");

        // Return fresh instance with relations
        return $student->load('user', 'class');
    }

    public function deleteStudent(string $id): Student
    {
        $student = Student::where('id', $id)->firstOrFail();

        DB::transaction(function () use ($student) {
            $student->user->delete(); // otomatis hapus teacher & pivot
        });

        Cache::forget("student.{$id}");

        return $student; // return object sebelum dihapus
    }
}
