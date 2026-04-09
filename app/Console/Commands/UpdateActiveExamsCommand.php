<?php

namespace App\Console\Commands;

use App\Models\ExamSchedule;
use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Carbon\Carbon;

#[Signature('exams:update-status')]
#[Description('Update exam status to active if schedule time is reached')]
class UpdateActiveExamsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $schedules = ExamSchedule::with('exam')
            ->whereIn('status', ['draft', 'scheduled', 'active'])
            ->get();

        foreach ($schedules as $schedule) {
            $examDate = Carbon::parse($schedule->exam_date)->format('Y-m-d');

            $startDateTime = Carbon::parse("{$examDate} {$schedule->start_time}");
            $endDateTime = Carbon::parse("{$examDate} {$schedule->end_time}");

            if ($now->lt($startDateTime)) {
                if ($schedule->status !== 'scheduled') {
                    $schedule->update(['status' => 'scheduled']);
                    $this->info("Schedule {$schedule->id} updated to scheduled");
                }

                continue;
            }

            if ($now->gte($startDateTime) && $now->lt($endDateTime)) {
                if ($schedule->status !== 'active') {
                    $schedule->update(['status' => 'active']);
                    $this->info("Schedule {$schedule->id} updated to active");
                }

                continue;
            }

            if ($now->gte($endDateTime)) {
                if ($schedule->status !== 'completed') {
                    $schedule->update(['status' => 'completed']);
                    $this->info("Schedule {$schedule->id} updated to completed");
                }
            }
        }
    }
}
