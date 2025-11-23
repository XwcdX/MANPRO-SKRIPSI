<?php

namespace App\Services;

use App\Models\TopicApplication;
use App\Models\StudentStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TopicApplicationService
{
    public function acceptApplication(string $applicationId, string $lecturerId, ?string $notes = null): array
    {
        $application = TopicApplication::with(['student', 'topic', 'period', 'lecturer'])->findOrFail($applicationId);
        
        if ($application->lecturer_id !== $lecturerId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $lecturer = $application->lecturer;
        $topic = $application->topic;
        $period = $application->period;
        
        $availableCapacity = $lecturer->getAvailableCapacityForPeriod($period->id);
        
        if ($availableCapacity < $topic->student_quota) {
            return [
                'success' => false,
                'message' => "Insufficient capacity. Topic requires {$topic->student_quota} slots but you only have {$availableCapacity} available."
            ];
        }

        DB::transaction(function () use ($application, $topic, $period, $lecturer, $notes) {
            $student = $application->student;
            $previousStatus = $student->status;

            $application->update([
                'status' => 'accepted',
                'lecturer_notes' => $notes,
            ]);

            $student->load([
                'supervisionApplications' => fn ($q) => $q->where('period_id', $student->activePeriod()->id)->whereIn('status', ['pending', 'accepted'])
            ]);

            foreach ($student->supervisionApplications as $application) {
                if ($application->status === 'pending') {
                    $application->status = 'canceled';
                } elseif ($application->status === 'accepted') {
                    $application->status = 'changed';
                }

                $application->save();
            }

            DB::table('student_lecturers')->insert([
                'id' => Str::uuid(),
                'student_id' => $application->student_id,
                'lecturer_id' => $application->lecturer_id,
                'role' => 0,
                'assignment_date' => now(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($student->status < 2) {
                $student->update([
                    'status' => 2,
                    'thesis_title' => $topic->topic,
                    'thesis_description' => $topic->description,
                ]);
                
                StudentStatusHistory::create([
                    'student_id' => $student->id,
                    'period_id' => $period->id,
                    'previous_status' => $previousStatus,
                    'new_status' => 2,
                    'changed_by' => $lecturer->id,
                    'reason' => 'Supervisor assigned via topic application acceptance',
                ]);
            }

            $topic->decrement('student_quota');
            if ($topic->student_quota <= 0) {
                $topic->update(['is_available' => false]);
                
                TopicApplication::where('topic_id', $topic->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'declined',
                        'lecturer_notes' => 'Topic quota has been filled.'
                    ]);
            }

            $newCapacity = $lecturer->getAvailableCapacityForPeriod($period->id);
            if ($newCapacity <= 0) {
                $lecturer->topics()
                    ->where('period_id', $period->id)
                    ->update(['is_available' => false]);
                
                TopicApplication::where('lecturer_id', $lecturer->id)
                    ->where('period_id', $period->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'declined',
                        'lecturer_notes' => 'Lecturer has reached maximum student capacity for this period.'
                    ]);
            }
        });

        return ['success' => true, 'message' => 'Application accepted. Student assigned as your supervisee.'];
    }

    public function declineApplication(string $applicationId, string $lecturerId, ?string $notes = null): array
    {
        $application = TopicApplication::findOrFail($applicationId);
        
        if ($application->lecturer_id !== $lecturerId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $application->update([
            'status' => 'declined',
            'lecturer_notes' => $notes,
        ]);

        return ['success' => true, 'message' => 'Application declined.'];
    }
}
