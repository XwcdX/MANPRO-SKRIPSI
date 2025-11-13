<?php

namespace App\Services;

use App\Models\LecturerAvailability;
use App\Models\Period;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AvailabilityService
{
    public function generateTimeSlots(Period $period): array
    {
        $timeSlots = [];
        $start = Carbon::parse($period->schedule_start_time ?? '07:30');
        $end = Carbon::parse($period->schedule_end_time ?? '18:00');
        
        while ($start->lt($end)) {
            $timeSlots[] = $start->format('H:i');
            $start->addMinutes(30);
        }
        
        return $timeSlots;
    }

    public function getDateRange(Period $period, string $type): array
    {
        if ($type === 'proposal_hearing') {
            $startDate = $period->proposal_hearing_start;
            $endDate = $period->proposal_hearing_end;
        } else {
            $startDate = $period->thesis_start;
            $endDate = $period->thesis_end;
        }

        if (!$startDate || !$endDate) {
            return [];
        }

        $dates = [];
        $dateRange = CarbonPeriod::create($startDate, $endDate);
        foreach ($dateRange as $date) {
            if ($date->dayOfWeek !== 0) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }

    public function loadAvailability(string $lecturerId, string $periodId, string $type, array $dates, array $timeSlots): array
    {
        $existing = LecturerAvailability::where('lecturer_id', $lecturerId)
            ->where('period_id', $periodId)
            ->where('type', $type)
            ->get();

        $availability = [];
        foreach ($existing as $item) {
            $key = Carbon::parse($item->date)->format('Y-m-d') . '_' . $item->time_slot;
            $availability[$key] = $item->is_available;
        }

        foreach ($dates as $date) {
            foreach ($timeSlots as $time) {
                $key = $date . '_' . $time;
                if (!isset($availability[$key])) {
                    $availability[$key] = true;
                }
            }
        }

        return $availability;
    }

    public function saveAvailability(string $lecturerId, string $periodId, string $type, array $availability): void
    {
        foreach ($availability as $key => $isAvailable) {
            [$date, $time] = explode('_', $key);
            
            LecturerAvailability::updateOrCreate(
                [
                    'lecturer_id' => $lecturerId,
                    'period_id' => $periodId,
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

    public function getPeriodsWithSchedules()
    {
        return Period::notArchived()
            ->where(function($query) {
                $query->whereNotNull('proposal_hearing_start')
                      ->orWhereNotNull('thesis_start');
            })
            ->orderBy('start_date', 'desc')
            ->get();
    }
}
