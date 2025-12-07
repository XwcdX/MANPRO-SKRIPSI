<?php

namespace App\Services;

use App\Models\Period;
use App\Models\PeriodSchedule;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodService
{
    public function getActivePeriod(): ?Period
    {
        return Period::notArchived()->orderBy('start_date', 'desc')->first();
    }

    public function findPeriod(string $periodId): ?Period
    {
        return Period::find($periodId);
    }

    public function createPeriod(array $data): Period
    {
        return DB::transaction(function () use ($data) {
            $data['name'] = $this->generatePeriodName($data['end_date']);

            if (Period::where('name', $data['name'])->exists()) {
                throw new \Exception('Period already exists');
            }

            $data['registration_end'] = $this->calculateRegistrationEnd($data);
            
            $period = Period::create($data);
            
            $this->createSchedules($period, $data);
            
            return $period;
        });
    }

    public function updatePeriod(Period $period, array $data): Period
    {
        return DB::transaction(function () use ($period, $data) {
            $data['name'] = $this->generatePeriodName($data['end_date']);

            $data['registration_end'] = $this->calculateRegistrationEnd($data);
            
            $period->update($data);
            
            $period->schedules()->delete();
            $this->createSchedules($period, $data);
            
            return $period;
        });
    }

    private function generatePeriodName(string $endDate): string
    {
        $endMonth = Carbon::parse($endDate)->month;
        $endYear = Carbon::parse($endDate)->year;
        
        if ($endMonth <= 2) {
            return "Gasal " . ($endYear - 1) . "/" . $endYear;
        } else {
            return "Genap " . $endYear . "/" . ($endYear + 1);
        }
    }

    private function calculateRegistrationEnd(array $data): string
    {
        if (!empty($data['proposal_schedules'])) {
            $earliestStart = collect($data['proposal_schedules'])
                ->pluck('start_date')
                ->min();
            
            if ($earliestStart) {
                return Carbon::parse($earliestStart)->subDay()->format('Y-m-d');
            }
        }
        
        return $data['start_date'];
    }

    private function createSchedules(Period $period, array $data): void
    {
        if (!empty($data['proposal_schedules'])) {
            foreach ($data['proposal_schedules'] as $schedule) {
                PeriodSchedule::create([
                    'period_id' => $period->id,
                    'type' => 'proposal_hearing',
                    'start_date' => $schedule['start_date'],
                    'end_date' => $schedule['end_date'],
                ]);
            }
        }
        
        if (!empty($data['thesis_schedules'])) {
            foreach ($data['thesis_schedules'] as $schedule) {
                PeriodSchedule::create([
                    'period_id' => $period->id,
                    'type' => 'thesis_defense',
                    'start_date' => $schedule['start_date'],
                    'end_date' => $schedule['end_date'],
                ]);
            }
        }
    }

    public function deletePeriod(string $periodId): bool
    {
        return DB::transaction(function () use ($periodId) {
            $period = Period::find($periodId);
            if ($period) {
                $period->schedules()->delete();
                $period->delete();
                return true;
            }
            return false;
        });
    }

    public function archivePeriod(string $periodId): bool
    {
        $period = Period::find($periodId);
        if ($period) {
            $period->archive();
            return true;
        }
        return false;
    }

    public function registerPeriod($student, Period $period): bool
    {
        if (!$student || !$period) {
            return false;
        }

        $student->periods()->syncWithoutDetaching([
            $period->id => [
                'id' => Str::uuid(),
                'enrollment_date' => now(),
                'is_active' => true,
            ]
        ]);

        return true;
    }
}
