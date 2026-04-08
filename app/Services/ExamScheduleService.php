<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\ExamSchedule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExamScheduleService
{
    private const MAX_PER_PAGE = 100;

    public function getAllSchedules(int $perPage = 10, string $search = ''): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return ExamSchedule::with('exam')
            ->when($search, fn($q) => $q->whereHas('exam', fn($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate($perPage);
    }

    public function getScheduleById(string $id): ExamSchedule
    {
        $schedule = ExamSchedule::with('exam')->where('id', $id)->first();

        if (!$schedule) {
            throw new DataNotFound('Jadwal ujian tidak ditemukan');
        }

        return $schedule;
    }

    public function createSchedule(array $data): ExamSchedule
    {
        $scheduleRequest = DB::transaction(function () use ($data) {
            return ExamSchedule::create($data);
        });
        $this->flushListCache();
        return $scheduleRequest;
    }

    public function updateSchedule(array $data, string $id): ExamSchedule
    {
        $schedule = ExamSchedule::where('id', $id)->first();

        if (!$schedule) {
            throw new DataNotFound('Jadwal ujian tidak ditemukan');
        }

        $scheduleRequest = DB::transaction(function () use ($data, $schedule) {
            $schedule->update($data);
            return $schedule->fresh('exam');
        });
        $this->flushListCache();
        return $scheduleRequest;
    }

    public function deleteSchedule(string $id): ExamSchedule
    {
        $schedule = ExamSchedule::where('id', $id)->first();

        if (!$schedule) {
            throw new DataNotFound('Jadwal ujian tidak ditemukan');
        }

        $scheduleRequest = DB::transaction(function () use ($schedule) {
            $deleted = $schedule->replicate();
            $schedule->delete();
            return $deleted;
        });
        $this->flushListCache();
        return $scheduleRequest;
    }
    private function flushListCache(): void
    {
        // Jika pakai Redis / Memcached — gunakan tags (direkomendasikan)
        // Cache::tags([self::CACHE_LIST_PREFIX])->flush();

        // Jika pakai driver tanpa tags — flush seluruh cache
        // (pertimbangkan ganti ke Redis agar tidak flush semua data)
        Cache::flush();
    }
}
