<?php

namespace App\Services;

use App\Models\Period;
use App\Models\PeriodSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * PeriodScheduleService
 * 
 * Handles business logic for managing period schedules (proposal hearings and thesis defenses).
 * Ensures schedules don't overlap and maintains data integrity.
 */
class PeriodScheduleService
{
    /**
     * Create a new period schedule.
     * Validates that the schedule doesn't overlap with existing schedules.
     * 
     * @param array $data Schedule data (period_id, type, start_date, end_date)
     * @return array Created schedule data
     * @throws ValidationException If schedule overlaps with existing schedules
     */
    public function createSchedule(array $data): array
    {
        // Validate date overlap
        $this->validateNoOverlap(
            $data['period_id'],
            $data['start_date'],
            $data['end_date']
        );

        $schedule = PeriodSchedule::create([
            'period_id' => $data['period_id'],
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        return [
            'id' => $schedule->id,
            'period_id' => $schedule->period_id,
            'type' => $schedule->type,
            'type_name' => $schedule->type_name,
            'start_date' => $schedule->start_date->format('Y-m-d'),
            'end_date' => $schedule->end_date->format('Y-m-d'),
            'date_range' => $schedule->date_range,
        ];
    }

    /**
     * Update an existing period schedule.
     * Validates that the updated schedule doesn't overlap with other schedules.
     * 
     * @param string $scheduleId The schedule UUID
     * @param array $data Updated schedule data
     * @return array Updated schedule data
     * @throws ValidationException If schedule overlaps with existing schedules
     */
    public function updateSchedule(string $scheduleId, array $data): array
    {
        $schedule = PeriodSchedule::findOrFail($scheduleId);

        $this->validateNoOverlap(
            $schedule->period_id,
            $data['start_date'],
            $data['end_date'],
            $scheduleId
        );

        $schedule->update([
            'type' => $data['type'] ?? $schedule->type,
            'start_date' => $data['start_date'] ?? $schedule->start_date,
            'end_date' => $data['end_date'] ?? $schedule->end_date,
        ]);

        return [
            'id' => $schedule->id,
            'period_id' => $schedule->period_id,
            'type' => $schedule->type,
            'type_name' => $schedule->type_name,
            'start_date' => $schedule->start_date->format('Y-m-d'),
            'end_date' => $schedule->end_date->format('Y-m-d'),
            'date_range' => $schedule->date_range,
        ];
    }

    /**
     * Delete a period schedule.
     * Cascades to lecturer_availability and thesis_presentations.
     * 
     * @param string $scheduleId The schedule UUID
     * @return bool Success status
     */
    public function deleteSchedule(string $scheduleId): bool
    {
        $schedule = PeriodSchedule::findOrFail($scheduleId);
        return $schedule->delete();
    }

    /**
     * Get all schedules for a period, ordered chronologically.
     * 
     * @param string $periodId The period UUID
     * @return array Array of schedule data
     */
    public function getSchedulesForPeriod(string $periodId): array
    {
        $schedules = PeriodSchedule::where('period_id', $periodId)
            ->ordered()
            ->get();

        return $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'period_id' => $schedule->period_id,
                'type' => $schedule->type,
                'type_name' => $schedule->type_name,
                'start_date' => $schedule->start_date->format('Y-m-d'),
                'end_date' => $schedule->end_date->format('Y-m-d'),
                'date_range' => $schedule->date_range,
                'is_active' => $schedule->isActive(),
                'is_upcoming' => $schedule->isUpcoming(),
                'has_ended' => $schedule->hasEnded(),
            ];
        })->toArray();
    }

    /**
     * Get upcoming proposal hearings for a period.
     * Used to determine if registration is still open.
     * 
     * @param string $periodId The period UUID
     * @return array Array of upcoming proposal hearing schedules
     */
    public function getUpcomingProposalHearings(string $periodId): array
    {
        $now = now();
        
        $schedules = PeriodSchedule::where('period_id', $periodId)
            ->where('type', 'proposal_hearing')
            ->where('start_date', '>', $now)
            ->ordered()
            ->get();

        return $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'start_date' => $schedule->start_date->format('Y-m-d'),
                'end_date' => $schedule->end_date->format('Y-m-d'),
                'date_range' => $schedule->date_range,
            ];
        })->toArray();
    }

    /**
     * Validate that a schedule doesn't overlap with existing schedules in the same period.
     * 
     * @param string $periodId The period UUID
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @param string|null $excludeScheduleId Schedule ID to exclude from check (for updates)
     * @return void
     * @throws ValidationException If overlap is detected
     */
    protected function validateNoOverlap(
        string $periodId,
        string $startDate,
        string $endDate,
        ?string $excludeScheduleId = null
    ): void {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $query = PeriodSchedule::where('period_id', $periodId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            });

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        $overlapping = $query->first();

        if ($overlapping) {
            throw ValidationException::withMessages([
                'date_range' => 'Schedule dates overlap with existing schedule: ' . $overlapping->date_range,
            ]);
        }
    }

    /**
     * Bulk create schedules for a period.
     * Useful for setting up multiple proposal hearings or thesis defenses at once.
     * 
     * @param string $periodId The period UUID
     * @param array $schedules Array of schedule data
     * @return array Array of created schedules
     */
    public function bulkCreateSchedules(string $periodId, array $schedules): array
    {
        $created = [];

        DB::transaction(function () use ($periodId, $schedules, &$created) {
            foreach ($schedules as $scheduleData) {
                $scheduleData['period_id'] = $periodId;
                $created[] = $this->createSchedule($scheduleData);
            }
        });

        return $created;
    }
}
