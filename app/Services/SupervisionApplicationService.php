<?php

namespace App\Services;

use App\Models\SupervisionApplication;
use App\Models\Student;
use App\Models\StudentStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupervisionApplicationService
{
    public function acceptApplication(string $applicationId, string $lecturerId): bool
    {
        return DB::transaction(function () use ($applicationId, $lecturerId) {
            $application = SupervisionApplication::with('division')->findOrFail($applicationId);
            $student = $application->student;
            
            $application->update(['status' => 'accepted']);
            
            DB::table('student_lecturers')->insert([
                'id' => Str::uuid(),
                'student_id' => $student->id,
                'lecturer_id' => $lecturerId,
                'role' => $application->proposed_role,
                'assignment_date' => now(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            if ($student->status < 2) {
                $previousStatus = $student->status;
                $student->update(['status' => 2]);
                
                StudentStatusHistory::create([
                    'student_id' => $student->id,
                    'period_id' => $application->period_id,
                    'previous_status' => $previousStatus,
                    'new_status' => 2,
                    'changed_by' => $lecturerId,
                    'reason' => 'Supervisor accepted - can now upload proposal',
                ]);
            }
            
            // TODO: Send notification to division head for approval
            // Division head is determined by application->division_id, not lecturer's primary_division_id
            
            return true;
        });
    }

    public function declineApplication(string $applicationId): bool
    {
        $application = SupervisionApplication::findOrFail($applicationId);
        $application->update(['status' => 'declined']);
        
        return true;
    }

    public function getApplicationsForLecturer(string $lecturerId, string $status, ?string $search = null)
    {
        return SupervisionApplication::where('lecturer_id', $lecturerId)
            ->where('status', $status)
            ->when($search, function ($query) use ($search) {
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->with(['student', 'period'])
            ->orderBy('created_at', 'desc');
    }
}
