<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Subject;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SubjectService
{
    // Durasi cache dalam detik
    private const CACHE_TTL     = 60;

    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;

    // =========================================================================
    // Read
    // =========================================================================

    public function getAllSubjects(string $search = '')
    {
        $search = trim($search);

        return Subject::when(
            $search !== '',
            fn($q) =>
            $q->where('name', 'like', "%{$search}%")
        )->get();
    }
    public function getSubjectById(string $id): ?Subject
    {
        $data = Cache::remember(
            "subject.{$id}",
            self::CACHE_TTL,
            fn() => Subject::with('user', 'class')->where('id', $id)->first()?->toArray()
        );

        return $data
            ? Subject::hydrate([$data])->first()
            : null;
    }

    // =========================================================================
    // Write
    // =========================================================================

    public function createSubject(array $data)
    {
        $subject = DB::transaction(function () use ($data) {
            $request_subject = Subject::create([
                'name' => $data['name'],
            ]);
            return $request_subject;
        });

        return $subject;
    }
    public function updateSubject(
        string $id,
        array $subjectData,
    ): Subject {
        // Ambil subject + user relasi
        $subject = Subject::where('id', $id)->first();

        if (!$subject) {
            throw new DataNotFound('Mata pelajaran tidak ditemukan');
        }
        DB::transaction(function () use ($subject, $subjectData) {
            // Update subject table
            $subject->update([
                'name' => $subjectData['name'] ?? $subject->name,
            ]);

            // Update related user table



            return $subject;
        });

        // Hapus cache
        Cache::forget("subject.{$id}");

        // Load user + classes untuk response
        return $subject;
    }

    public function deleteSubject(string $id): Subject
    {
        $subject = Subject::where('id', $id)->firstOrFail();

        DB::transaction(function () use ($subject) {
            $subject->user->delete(); // otomatis hapus teacher & pivot
        });

        Cache::forget("subject.{$id}");

        return $subject; // return object sebelum dihapus
    }
}
