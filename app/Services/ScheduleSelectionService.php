<?php

namespace App\Services;

use App\Models\PeriodSchedule;
use App\Models\Student;
use Carbon\Carbon;
use Exception;

class ScheduleSelectionService
{
    public function choose(Student $user, string $scheduleId, string $type): void
    {
        $column = $type === 'final' ? 'final_schedule_id' : 'proposal_schedule_id';

        $schedule = PeriodSchedule::find($scheduleId);

        if (!$schedule) {
            throw new Exception("Jadwal tidak ditemukan.");
        }

        $deadline = $schedule->deadline;

        if (now()->greaterThan($deadline)) {
            throw new Exception("Batas pendaftaran jadwal sudah dilewati.");
        }

        $user->update([$column => $scheduleId]);
    }


    public function cancel(Student $user, string $type): void
    {
        $column = $type === 'final' ? 'final_schedule_id' : 'proposal_schedule_id';

        $scheduleId = $user->$column;

        if (!$scheduleId) {
            throw new Exception("Tidak ada jadwal yang dipilih.");
        }

        $schedule = PeriodSchedule::find($scheduleId);

        if (!$schedule) {
            throw new Exception("Jadwal tidak ditemukan.");
        }

        $deadline = $schedule->deadline;

        if (now()->greaterThan($deadline)) {
            throw new Exception("Batas pembatalan jadwal sudah terlewati.");
        }

        $user->update([$column => null]);
    }
}
