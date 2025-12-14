<?php

namespace App\Services;

use App\Models\ThesisPresentation;
use App\Models\PresentationExaminer;
use App\Models\LecturerAvailability;
use App\Models\PeriodSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PresentationService
 * 
 * Handles business logic for thesis presentation scheduling.
 * Works with period schedules to manage proposal hearings and thesis defenses.
 */
class PresentationService
{
    /**
     * Create a new thesis presentation.
     * 
     * @param array $data Presentation data including period_schedule_id, venue_id, student_id, etc.
     * @return ThesisPresentation The created presentation
     */
    public function createPresentation(array $data): ThesisPresentation
    {
        return DB::transaction(function () use ($data) {
            $presentation = ThesisPresentation::create([
                'period_schedule_id' => $data['period_schedule_id'],
                'venue_id' => $data['venue_id'],
                'student_id' => $data['student_id'],
                'presentation_date' => $data['presentation_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'notes' => $data['notes'] ?? null,
            ]);

            if (!empty($data['lead_examiner_id'])) {
                PresentationExaminer::create([
                    'thesis_presentation_id' => $presentation->id,
                    'lecturer_id' => $data['lead_examiner_id'],
                    'is_lead_examiner' => true,
                ]);
            }

            if (!empty($data['examiner_ids'])) {
                foreach ($data['examiner_ids'] as $lecturerId) {
                    PresentationExaminer::create([
                        'thesis_presentation_id' => $presentation->id,
                        'lecturer_id' => $lecturerId,
                        'is_lead_examiner' => false,
                    ]);
                }
            }

            return $presentation;
        });
    }

    /**
     * Update an existing thesis presentation.
     * 
     * @param ThesisPresentation $presentation The presentation to update
     * @param array $data Updated presentation data
     * @return ThesisPresentation The updated presentation
     */
    public function updatePresentation(ThesisPresentation $presentation, array $data): ThesisPresentation
    {
        return DB::transaction(function () use ($presentation, $data) {
            $presentation->update([
                'period_schedule_id' => $data['period_schedule_id'],
                'venue_id' => $data['venue_id'],
                'student_id' => $data['student_id'],
                'presentation_date' => $data['presentation_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'notes' => $data['notes'] ?? null,
            ]);

            $presentation->examiners()->delete();

            if (!empty($data['lead_examiner_id'])) {
                PresentationExaminer::create([
                    'thesis_presentation_id' => $presentation->id,
                    'lecturer_id' => $data['lead_examiner_id'],
                    'is_lead_examiner' => true,
                ]);
            }

            if (!empty($data['examiner_ids'])) {
                foreach ($data['examiner_ids'] as $lecturerId) {
                    PresentationExaminer::create([
                        'thesis_presentation_id' => $presentation->id,
                        'lecturer_id' => $lecturerId,
                        'is_lead_examiner' => false,
                    ]);
                }
            }

            return $presentation->fresh();
        });
    }

    /**
     * Delete a thesis presentation.
     * Cascades to presentation_examiners.
     * 
     * @param string $presentationId The presentation UUID
     * @return bool Success status
     */
    public function deletePresentation(string $presentationId): bool
    {
        $presentation = ThesisPresentation::find($presentationId);
        if ($presentation) {
            $presentation->delete();
            return true;
        }
        return false;
    }

    /**
     * Get available lecturers for a specific date and time, excluding supervisors.
     * 
     * @param string $periodScheduleId The period schedule UUID
     * @param string $date The presentation date (Y-m-d format)
     * @param string $startTime The start time (H:i format)
     * @param string $endTime The end time (H:i format)
     * @param array $studentIds Student IDs to exclude their supervisors
     * @param string|null $excludePresentationId Presentation ID to exclude (for editing)
     * @return array Array of available lecturer data
     */
    public function getAvailableLecturers(
        string $periodScheduleId,
        string $date,
        string $startTime,
        string $endTime,
        array $studentIds = [],
        ?string $excludePresentationId = null
    ): array {
        $timeSlot = $startTime . '-' . $endTime;
        
        // Lecturers assigned to presentations at this time
        $busyFromPresentations = PresentationExaminer::whereHas('thesisPresentation', function ($query) use ($date, $startTime, $endTime, $excludePresentationId) {
            $query->where('presentation_date', $date)
                ->when($excludePresentationId, function($q) use ($excludePresentationId) {
                    $q->where('id', '!=', $excludePresentationId);
                })
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($q2) use ($startTime, $endTime) {
                          $q2->where('start_time', '<=', $startTime)
                             ->where('end_time', '>=', $endTime);
                      });
                });
        })->pluck('lecturer_id')->unique()->toArray();
        
        $busyFromAvailability = LecturerAvailability::where('period_schedule_id', $periodScheduleId)
            ->where('date', $date)
            ->where('time_slot', $timeSlot)
            ->where('is_available', false)
            ->pluck('lecturer_id')
            ->toArray();
        
        $supervisorIds = [];
        if (!empty($studentIds)) {
            $supervisorIds = DB::table('student_lecturers')
                ->whereIn('student_id', $studentIds)
                ->whereIn('role', [0, 1])
                ->where('status', 'active')
                ->pluck('lecturer_id')
                ->unique()
                ->toArray();
        }
        
        $busyIds = array_merge($busyFromPresentations, $busyFromAvailability, $supervisorIds);
        
        return \App\Models\Lecturer::whereNotIn('id', $busyIds)
            ->orderBy('name')
            ->get()
            ->toArray();
    }
    
    public function getValidCombinations(string $periodScheduleId, array $studentIds, ?string $excludePresentationId = null): array
    {
        if (empty($studentIds)) {
            return [];
        }

        $schedule = PeriodSchedule::find($periodScheduleId);
        if (!$schedule) {
            return [];
        }

        $supervisorIds = DB::table('student_lecturers')
            ->whereIn('student_id', $studentIds)
            ->whereIn('role', [0, 1])
            ->where('status', 'active')
            ->pluck('lecturer_id')
            ->unique()
            ->toArray();

        if (empty($supervisorIds)) {
            return [];
        }

        $period = $schedule->period;
        $type = $schedule->type === 'proposal_hearing' ? 'proposal_hearing' : 'thesis';
        $timeSlots = app(AvailabilityService::class)->generateTimeSlotsWithoutBreak($period, $type);

        $dates = [];
        $current = Carbon::parse($schedule->start_date);
        $end = Carbon::parse($schedule->end_date);
        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $busyLecturers = [];
        foreach ($dates as $date) {
            foreach ($timeSlots as $slot) {
                [$startTime, $endTime] = explode('-', $slot);
                
                $busy = PresentationExaminer::whereHas('thesisPresentation', function ($query) use ($date, $startTime, $endTime, $excludePresentationId) {
                    $query->where('presentation_date', $date)
                        ->when($excludePresentationId, function($q) use ($excludePresentationId) {
                            $q->where('id', '!=', $excludePresentationId);
                        })
                        ->where(function ($q) use ($startTime, $endTime) {
                            $q->whereBetween('start_time', [$startTime, $endTime])
                              ->orWhereBetween('end_time', [$startTime, $endTime])
                              ->orWhere(function ($q2) use ($startTime, $endTime) {
                                  $q2->where('start_time', '<=', $startTime)
                                     ->where('end_time', '>=', $endTime);
                              });
                        });
                })->pluck('lecturer_id')->unique()->toArray();
                
                $unavailable = LecturerAvailability::where('period_schedule_id', $periodScheduleId)
                    ->where('date', $date)
                    ->where('time_slot', $slot)
                    ->where('is_available', false)
                    ->pluck('lecturer_id')
                    ->toArray();
                
                $busyLecturers["{$date}|{$slot}"] = array_merge($busy, $unavailable);
            }
        }

        $totalLecturers = \App\Models\Lecturer::count();
        $combinations = [];

        foreach ($dates as $date) {
            foreach ($timeSlots as $slot) {
                $key = "{$date}|{$slot}";
                $busyIds = $busyLecturers[$key] ?? [];
                
                $supervisorsBusy = array_intersect($supervisorIds, $busyIds);
                if (!empty($supervisorsBusy)) {
                    continue;
                }
                
                $availableCount = $totalLecturers - count(array_unique(array_merge($busyIds, $supervisorIds)));
                if ($availableCount < 2) {
                    continue;
                }
                
                $combinations[] = [
                    'date' => $date,
                    'time' => $slot,
                    'label' => \Carbon\Carbon::parse($date)->format('d M Y') . ' • ' . $slot,
                ];
            }
        }

        return $combinations;
    }

    /**
     * Get presentations for a lecturer (as examiner or supervisor).
     * 
     * @param string $lecturerId Lecturer UUID
     * @return array Array of presentation data
     */
    public function getLecturerPresentations(string $lecturerId): array
    {
        return ThesisPresentation::with(['student', 'venue', 'periodSchedule.period', 'examiners.lecturer', 'leadExaminer.lecturer'])
            ->where(function($q) use ($lecturerId) {
                $q->whereHas('examiners', fn($q) => $q->where('lecturer_id', $lecturerId))
                  ->orWhereHas('student.supervisors', fn($q) => $q->where('lecturer_id', $lecturerId));
            })
            ->orderBy('presentation_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Submit presentation decision (pass/fail).
     * 
     * @param string $presentationId Presentation UUID
     * @param string $decision 'pass' or 'fail'
     * @param string|null $notes Optional notes
     * @return bool Success status
     */
    public function submitDecision(string $presentationId, string $decision, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($presentationId, $decision, $notes) {
            $presentation = ThesisPresentation::with(['student', 'periodSchedule'])->find($presentationId);
            if (!$presentation) return false;

            $student = $presentation->student;
            $oldStatus = $student->status;
            
            if ($decision === 'pass') {
                $newStatus = 4;
                $student->update(['status' => $newStatus]);
            } else {
                $newStatus = 2;
                $scheduleType = $presentation->periodSchedule->type;
                $updateData = ['status' => $newStatus];
                
                if ($scheduleType === 'proposal_hearing') {
                    $updateData['proposal_schedule_id'] = null;
                } else {
                    $updateData['thesis_schedule_id'] = null;
                }
                
                $student->update($updateData);
            }

            \App\Models\StudentStatusHistory::create([
                'student_id' => $student->id,
                'period_id' => $presentation->periodSchedule->period_id,
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => auth()->id(),
                'reason' => $notes ?: ($decision === 'pass' ? 'Passed presentation' : 'Failed presentation'),
            ]);

            return true;
        });
    }
}
