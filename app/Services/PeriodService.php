<?php

namespace App\Services;

use App\Models\Period;
use Illuminate\Support\Str;

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
        $period = new Period();
        $period->fill($data);
        $period->save();
        
        return $period;
    }

    public function updatePeriod(Period $period, array $data): Period
    {
        $period->fill($data);
        $period->save();
        
        return $period;
    }

    public function deletePeriod(string $periodId): bool
    {
        $period = Period::find($periodId);
        if ($period) {
            $period->delete();
            return true;
        }
        return false;
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
