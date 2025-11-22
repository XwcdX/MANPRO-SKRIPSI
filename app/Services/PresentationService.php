<?php

namespace App\Services;

use App\Models\ThesisPresentation;
use App\Models\PresentationExaminer;
use App\Models\LecturerAvailability;
use Illuminate\Support\Facades\DB;

class PresentationService
{
    public function createPresentation(array $data): ThesisPresentation
    {
        return DB::transaction(function () use ($data) {
            $presentation = ThesisPresentation::create([
                'period_id' => $data['period_id'],
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

    public function updatePresentation(ThesisPresentation $presentation, array $data): ThesisPresentation
    {
        return DB::transaction(function () use ($presentation, $data) {
            $presentation->update([
                'period_id' => $data['period_id'],
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

    public function deletePresentation(string $presentationId): bool
    {
        $presentation = ThesisPresentation::find($presentationId);
        if ($presentation) {
            $presentation->delete();
            return true;
        }
        return false;
    }

    public function getAvailableLecturers(string $periodId, string $date, string $startTime, string $endTime, ?string $excludePresentationId = null): array
    {
        $busyLecturers = PresentationExaminer::whereHas('thesisPresentation', function ($query) use ($date, $startTime, $endTime, $excludePresentationId) {
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

        $allLecturers = \App\Models\Lecturer::pluck('id')->toArray();
        return array_diff($allLecturers, $busyLecturers);
    }
}
