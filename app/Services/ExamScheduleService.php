<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\ExamSchedule;
use Illuminate\Pagination\LengthAwarePaginator;
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
        return DB::transaction(function () use ($data) {
            return ExamSchedule::create($data);
        });
    }

    public function updateSchedule(array $data, string $id): ExamSchedule
    {
        $schedule = ExamSchedule::where('id', $id)->first();

        if (!$schedule) {
            throw new DataNotFound('Jadwal ujian tidak ditemukan');
        }

        return DB::transaction(function () use ($data, $schedule) {
            $schedule->update($data);
            return $schedule->fresh('exam');
        });
    }

    public function deleteSchedule(string $id): ExamSchedule
    {
        $schedule = ExamSchedule::where('id', $id)->first();

        if (!$schedule) {
            throw new DataNotFound('Jadwal ujian tidak ditemukan');
        }

        return DB::transaction(function () use ($schedule) {
            $deleted = $schedule->replicate();
            $schedule->delete();
            return $deleted;
        });
    }
}
