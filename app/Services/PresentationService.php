<?php

namespace App\Services;

use App\Models\ThesisPresentation;
use App\Models\PresentationExaminer;
use App\Models\LecturerAvailability;
use App\Models\PeriodSchedule;
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
    
    /**
     * Check for existing presentation at same venue, date, and time.
     * 
     * @param string $venueId Venue UUID
     * @param string $date Presentation date
     * @param string $startTime Start time
     * @param string $endTime End time
     * @return array|null Existing presentation info or null
     */
    public function findExistingPresentation(string $venueId, string $date, string $startTime, string $endTime): ?array
    {
        $existing = ThesisPresentation::where('venue_id', $venueId)
            ->where('presentation_date', $date)
            ->where('start_time', $startTime)
            ->where('end_time', $endTime)
            ->with(['venue', 'leadExaminer.lecturer', 'examiners.lecturer'])
            ->first();

        if (!$existing) {
            return null;
        }

        return [
            'venue' => $existing->venue->name,
            'date' => \Carbon\Carbon::parse($existing->presentation_date)->format('d M Y'),
            'time' => substr($existing->start_time, 0, 5) . '-' . substr($existing->end_time, 0, 5),
            'lead_examiner_id' => $existing->leadExaminer?->lecturer_id,
            'examiner_ids' => $existing->examiners()->where('is_lead_examiner', false)->pluck('lecturer_id')->toArray(),
        ];
    }
}
