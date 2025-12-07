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
                'presentation_type' => $data['presentation_type'],
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
                'presentation_type' => $data['presentation_type'],
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
     * Get available lecturers for a specific date and time.
     * Checks both lecturer availability settings and existing presentation conflicts.
     * 
     * @param string $periodScheduleId The period schedule UUID
     * @param string $date The presentation date (Y-m-d format)
     * @param string $startTime The start time (H:i format)
     * @param string $endTime The end time (H:i format)
     * @param string|null $excludePresentationId Presentation ID to exclude (for editing)
     * @return array Array of available lecturer IDs
     */
    public function getAvailableLecturers(
        string $periodScheduleId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $excludePresentationId = null
    ): array {
        // Get the period schedule to determine type
        $schedule = PeriodSchedule::findOrFail($periodScheduleId);
        $type = $schedule->type === 'proposal_hearing' ? 'proposal' : 'thesis';
        
        // Create time slot string for availability check
        $timeSlot = $startTime . '-' . $endTime;
        
        // Get lecturers who marked themselves as available for this date/time
        $availableLecturers = LecturerAvailability::where('period_schedule_id', $periodScheduleId)
            ->where('date', $date)
            ->where('time_slot', $timeSlot)
            ->where('is_available', true)
            ->pluck('lecturer_id')
            ->toArray();
        
        // Get lecturers who are busy (already assigned to presentations at this time)
        $busyLecturers = PresentationExaminer::whereHas('thesisPresentation', function ($query) use ($date, $startTime, $endTime, $excludePresentationId) {
            $query->where('presentation_date', $date)
                ->when($excludePresentationId, function($q) use ($excludePresentationId) {
                    $q->where('id', '!=', $excludePresentationId);
                })
                ->where(function ($q) use ($startTime, $endTime) {
                    // Check for time overlap
                    $q->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($q2) use ($startTime, $endTime) {
                          $q2->where('start_time', '<=', $startTime)
                             ->where('end_time', '>=', $endTime);
                      });
                });
        })->pluck('lecturer_id')->unique()->toArray();
        
        // Return lecturers who are available AND not busy
        return array_diff($availableLecturers, $busyLecturers);
    }
    
    /**
     * Get all presentations for a specific period schedule.
     * 
     * @param string $periodScheduleId The period schedule UUID
     * @return array Array of presentation data
     */
    public function getPresentationsForSchedule(string $periodScheduleId): array
    {
        $presentations = ThesisPresentation::where('period_schedule_id', $periodScheduleId)
            ->with(['student', 'venue', 'examiners.lecturer', 'leadExaminer.lecturer'])
            ->orderBy('presentation_date')
            ->orderBy('start_time')
            ->get();
        
        return $presentations->map(function ($presentation) {
            return [
                'id' => $presentation->id,
                'student_name' => $presentation->student->name,
                'venue_name' => $presentation->venue->name,
                'presentation_date' => $presentation->presentation_date,
                'start_time' => $presentation->start_time,
                'end_time' => $presentation->end_time,
                'presentation_type' => $presentation->presentation_type,
                'lead_examiner' => $presentation->leadExaminer ? $presentation->leadExaminer->lecturer->name : null,
                'examiners' => $presentation->examiners->where('is_lead_examiner', false)->pluck('lecturer.name')->toArray(),
            ];
        })->toArray();
    }
}
