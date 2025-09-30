<?php

namespace App\Services;

use App\Models\Period;
use Carbon\Carbon;

class PeriodService
{
    public function getActivePeriod(): ?Period
    {
        return Period::active();
    }
    
    public function canStudentRegister(): bool
    {
        $period = $this->getActivePeriod();
        return $period && $period->isRegistrationOpen();
    }
    
    public function createPeriod(array $data): Period
    {
        if ($data['end_date'] <= $data['start_date']) {
            throw new \InvalidArgumentException('End date must be after start date');
        }
        
        return Period::create($data);
    }
    
    public function activatePeriod(Period $period): void
    {
        Period::where('id', '!=', $period->id)->update(['is_active' => false]);
        $period->update(['is_active' => true, 'status' => 'registration_open']);
    }
    
    public function closePeriod(Period $period): void
    {
        $period->update(['is_active' => false, 'status' => 'completed']);
    }
}