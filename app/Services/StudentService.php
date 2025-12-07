<?php

namespace App\Services;

use App\Models\Period;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentService
{
    public function getPeriods(string $orderBy = 'desc', bool $asArray = false)
    {
        $query = Period::notArchived()->orderBy('start_date', $orderBy);
        $result = $query->get();
        return $asArray ? $result->toArray() : $result;
    }

    public function getActivePeriod(): ?Period
    {
        return $this->getPeriods('desc')->first();
    }

    public function getStartedPeriods()
    {
        return $this->getPeriods('desc');
    }

    public function getActivePeriods(): array
    {
        return $this->getPeriods('asc', true);
    }

    public function getStudentsByPeriod(string $periodId, ?string $search = null): Builder
    {
        return Student::whereHas('periods', function($query) use ($periodId) {
            $query->where('periods.id', $periodId);
        })->when($search, function($query) use ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        })->with(['supervisors']);
    }

    public function getStudentsByLecturerRole(string $lecturerId, int $role, ?string $periodId = null): array
    {
        return Student::select('students.*')
            ->whereHas('supervisors', function($query) use ($lecturerId, $role) {
                $query->where('lecturer_id', $lecturerId)
                      ->where('role', $role)
                      ->where('status', 'active');
            })->when($periodId, function($query) use ($periodId) {
                $query->whereHas('periods', function($q) use ($periodId) {
                    $q->where('periods.id', $periodId);
                });
            })->with([
                'latestProposal', 
                'history_proposals' => function($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'latestThesis',
                'history_theses' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])->get()->map(function($student) {
                $data = $student->toArray();
                $data['student_number'] = explode('@', $student->email)[0];
                return $data;
            })->toArray();
    }

    public function getSupervisor1Students(string $lecturerId, ?string $periodId = null): array
    {
        return $this->getStudentsByLecturerRole($lecturerId, 0, $periodId);
    }

    public function getSupervisor2Students(string $lecturerId, ?string $periodId = null): array
    {
        return $this->getStudentsByLecturerRole($lecturerId, 1, $periodId);
    }

    public function getDivisionStudents(string $divisionId, ?string $periodId = null): array
    {
        return Student::where('division_id', $divisionId)
            ->when($periodId, function($query) use ($periodId) {
                $query->whereHas('periods', function($q) use ($periodId) {
                    $q->where('periods.id', $periodId);
                });
            })
            ->with([
                'latestProposal',
                'history_proposals' => function($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'latestThesis',
                'history_theses' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])
            ->get()
            ->map(function($student) {
                $data = $student->toArray();
                $data['student_number'] = explode('@', $student->email)[0];
                return $data;
            })
            ->toArray();
    }

    public function resign(Student $user)
    {
        try {
            DB::transaction(function () use ($user) {

                $user->periods()
                    ->wherePivot('is_active', true)
                    ->detach();

                $user->update([
                    'division_id' => null,
                    'thesis_title' => null,
                    'thesis_description' => null,
                    'status' => 0,
                    'head_division_comment' => null,
                    'revision_notes' => null,
                    'final_thesis_path' => null,
                    'final_proposal_path' => null,
                    'due_date' => null
                ]);

                $user->supervisors()
                    ->wherePivot('status', true)
                    ->detach();

                $user->examiners()
                    ->wherePivot('status', true)
                    ->detach();

                $user->topicApplications()->delete();
                $user->history_proposals()->delete();
                $user->history_theses()->delete();
                $user->supervisionApplications()->delete();
            });

        } catch (\Throwable $e) {

            Log::error('Resign failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}