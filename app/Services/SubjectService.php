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

        $query = Subject::select('id', 'name', 'created_at', 'updated_at');

        // Use full-text search for better performance
        if (!empty($search)) {
            $query = SearchService::apply($query, $search, 'search_vector');
        }

        return $query->get();
    }
    public function getSubjectById(string $id): ?Subject
    {
        $data = Cache::tags(['subject'])->remember(
            "subject.{$id}",
            self::CACHE_TTL,
            fn() => Subject::select('id', 'name', 'created_at', 'updated_at')->where('id', $id)->first()?->toArray()
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

        // Cache invalidation menggunakan tags
        Cache::tags(['subject'])->flush();

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
