<?php

namespace App\Services;

use App\Models\LecturerAvailability;
use App\Models\Period;
use App\Models\PeriodSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AvailabilityService
{
    /**
     * Generate time slots for a period schedule with break time support.
     * Creates slots based on slot duration (45 minutes) and automatically handles break time.
     * 
     * @param Period $period The period containing time settings
     * @param string $type The type of schedule ('proposal_hearing' or 'thesis_defense')
     * @return array Array of time slot strings (e.g., ['08:00-08:45', '08:45-09:30', ...])
     */
    public function generateTimeSlots(Period $period, string $type = 'proposal_hearing'): array
    {
        $timeSlots = [];
        
        // Determine which schedule times to use based on type
        if ($type === 'proposal_hearing') {
            $startTime = $period->proposal_schedule_start_time ?? '08:00:00';
            $endTime = $period->proposal_schedule_end_time ?? '16:00:00';
            $slotDuration = $period->proposal_slot_duration ?? 45;
        } else {
            $startTime = $period->thesis_schedule_start_time ?? '08:00:00';
            $endTime = $period->thesis_schedule_end_time ?? '16:00:00';
            $slotDuration = $period->thesis_slot_duration ?? 45;
        }
        
        $breakStart = Carbon::parse($period->break_start_time ?? '12:00:00');
        $breakEnd = Carbon::parse($period->break_end_time ?? '13:00:00');
        
        $current = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        
        while ($current->lt($end)) {
            $slotEnd = $current->copy()->addMinutes($slotDuration);
            
            if ($slotEnd->gt($breakStart) && $current->lt($breakStart)) {
                $timeSlots[] = [
                    'value' => $current->format('H:i') . '-' . $breakEnd->format('H:i'),
                    'label' => $current->format('H:i') . ' - ' . $breakEnd->format('H:i') . ' (Break)',
                    'disabled' => true,
                ];
                $current = $breakEnd->copy();
                continue;
            }
            
            // Skip if we're in break time
            if ($current->gte($breakStart) && $current->lt($breakEnd)) {
                $current = $breakEnd->copy();
                continue;
            }
            
            // Add regular slot
            if ($slotEnd->lte($end)) {
                $timeSlots[] = [
                    'value' => $current->format('H:i') . '-' . $slotEnd->format('H:i'),
                    'label' => $current->format('H:i') . ' - ' . $slotEnd->format('H:i'),
                    'disabled' => false,
                ];
            }
            
            $current->addMinutes($slotDuration);
        }
        
        return $timeSlots;
    }
    
    /**
     * Generate simple time slot strings (for backward compatibility).
     * 
     * @param Period $period
     * @param string $type
     * @return array Array of time slot strings (with break indicator)
     */
    public function generateSimpleTimeSlots(Period $period, string $type = 'proposal_hearing'): array
    {
        $slots = $this->generateTimeSlots($period, $type);
        return array_map(function($slot) {
            if (is_array($slot)) {
                return $slot['disabled'] ? $slot['value'] . ' (Break)' : $slot['value'];
            }
            return $slot;
        }, $slots);
    }

    /**
     * Generate time slots without break time (for presentation scheduling).
     * 
     * @param Period $period
     * @param string $type
     * @return array Array of time slot strings without break
     */
    public function generateTimeSlotsWithoutBreak(Period $period, string $type = 'proposal_hearing'): array
    {
        $slots = $this->generateTimeSlots($period, $type);
        $result = [];
        foreach ($slots as $slot) {
            if (is_array($slot) && !$slot['disabled']) {
                $result[] = $slot['value'];
            }
        }
        return $result;
    }

    /**
     * Get date range for a specific period schedule.
     * 
     * @param PeriodSchedule $schedule The period schedule
     * @return array Array of date strings (Y-m-d format), excluding Sundays
     */
    public function getDateRangeForSchedule(PeriodSchedule $schedule): array
    {
        $dates = [];
        $dateRange = CarbonPeriod::create($schedule->start_date, $schedule->end_date);
        
        foreach ($dateRange as $date) {
            // Exclude Sundays
            if ($date->dayOfWeek !== 0) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }

    /**
     * Get date range for a period and type (backward compatibility).
     * 
     * @param Period $period The period
     * @param string $type The type ('proposal_hearing' or 'thesis')
     * @return array Array of date strings (Y-m-d format), excluding Sundays
     */
    public function getDateRange(Period $period, string $type): array
    {
        $schedule = $period->schedules()->where('type', $type)->first();
        if (!$schedule) {
            return [];
        }
        
        return $this->getDateRangeForSchedule($schedule);
    }

    /**
     * Load lecturer availability for a specific period schedule.
     * 
     * @param string $lecturerId The lecturer UUID
     * @param string $periodScheduleId The period schedule UUID
     * @param array $dates Array of date strings
     * @param array $timeSlots Array of time slot strings or arrays
     * @return array Associative array with 'date_timeslot' keys and boolean values
     */
    public function loadAvailability(string $lecturerId, string $periodScheduleId, array $dates, array $timeSlots): array
    {
        $existing = LecturerAvailability::where('lecturer_id', $lecturerId)
            ->where('period_schedule_id', $periodScheduleId)
            ->get();

        $availability = [];
        
        // Initialize all date-time combinations with default availability FIRST
        foreach ($dates as $date) {
            foreach ($timeSlots as $slot) {
                // Handle both array and string time slots
                $timeValue = is_array($slot) ? $slot['value'] : $slot;
                
                // Skip break slots
                if (str_contains($timeValue, 'Break')) {
                    continue;
                }
                
                $key = $date . '_' . $timeValue;
                // Default to true (available)
                $availability[$key] = true;
            }
        }
        
        // Then override with existing data from database
        foreach ($existing as $item) {
            $key = Carbon::parse($item->date)->format('Y-m-d') . '_' . $item->time_slot;
            $availability[$key] = $item->is_available;
        }

        return $availability;
    }

    /**
     * Load availability by period and type (backward compatibility).
     * 
     * @param string $lecturerId The lecturer UUID
     * @param string $periodId The period UUID
     * @param string $type The type ('proposal_hearing' or 'thesis')
     * @param array $dates Array of date strings
     * @param array $timeSlots Array of time slot strings or arrays
     * @return array Associative array with 'date_timeslot' keys and boolean values
     */
    public function loadAvailabilityByPeriod(string $lecturerId, string $periodId, string $type, array $dates, array $timeSlots): array
    {
        $period = Period::find($periodId);
        if (!$period) return [];
        
        $schedule = $period->schedules()->where('type', $type)->first();
        if (!$schedule) return [];
        
        return $this->loadAvailability($lecturerId, $schedule->id, $dates, $timeSlots);
    }

    /**
     * Save lecturer availability for a specific period schedule.
     * 
     * @param string $lecturerId The lecturer UUID
     * @param string $periodScheduleId The period schedule UUID
     * @param string $type The type ('proposal_hearing' or 'thesis')
     * @param array $availability Associative array with 'date_timeslot' keys and boolean values
     * @return void
     */
    public function saveAvailability(string $lecturerId, string $periodScheduleId, string $type, array $availability): void
    {
        foreach ($availability as $key => $isAvailable) {
            [$date, $time] = explode('_', $key, 2);
            
            LecturerAvailability::updateOrCreate(
                [
                    'lecturer_id' => $lecturerId,
                    'period_schedule_id' => $periodScheduleId,
                    'type' => $type,
                    'date' => $date,
                    'time_slot' => $time,
                ],
                [
                    'is_available' => $isAvailable,
                ]
            );
        }
    }

    /**
     * Save availability by period and type (backward compatibility).
     * 
     * @param string $lecturerId The lecturer UUID
     * @param string $periodId The period UUID
     * @param string $type The type ('proposal_hearing' or 'thesis')
     * @param array $availability Associative array with 'date_timeslot' keys and boolean values
     * @return void
     */
    public function saveAvailabilityByPeriod(string $lecturerId, string $periodId, string $type, array $availability): void
    {
        $period = Period::find($periodId);
        if (!$period) return;
        
        $schedule = $period->schedules()->where('type', $type)->first();
        if (!$schedule) return;
        
        $this->saveAvailability($lecturerId, $schedule->id, $type, $availability);
    }

    /**
     * Get all periods that have schedules.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPeriodsWithSchedules()
    {
        return Period::notArchived()
            ->whereHas('schedules')
            ->with('schedules')
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Get locked time slots for a lecturer (presentations where they're examiner or supervisor).
     * 
     * @param string $lecturerId Lecturer UUID
     * @param string $periodScheduleId Period schedule UUID
     * @return array Array with slot keys and student names
     */
    public function getLockedSlots(string $lecturerId, string $periodScheduleId): array
    {
        $presentations = \App\Models\ThesisPresentation::where('period_schedule_id', $periodScheduleId)
            ->where(function($q) use ($lecturerId) {
                $q->whereHas('examiners', fn($q) => $q->where('lecturer_id', $lecturerId))
                  ->orWhereHas('student.supervisors', fn($q) => $q->where('lecturer_id', $lecturerId));
            })
            ->with('student')
            ->get();

        $locked = [];
        foreach ($presentations as $p) {
            $date = Carbon::parse($p->presentation_date)->format('Y-m-d');
            $timeSlot = substr($p->start_time, 0, 5) . '-' . substr($p->end_time, 0, 5);
            $key = $date . '_' . $timeSlot;
            
            if (!isset($locked[$key])) {
                $locked[$key] = [];
            }
            $locked[$key][] = $p->student->name;
        }
        
        return $locked;
    }
}
