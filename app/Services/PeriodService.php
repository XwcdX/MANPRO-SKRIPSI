<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Support\Str;

class PeriodService
{
    protected CrudService $crud;

    public function __construct(CrudService $crud)
    {
        $this->crud = $crud;
    }

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

    public function registerPeriod(Student $student, Period $period): bool
    {
        try {
            // Nonaktifkan semua periode sebelumnya
            $student->periods()->update(['is_active' => false]);

            // Tambahkan periode baru sebagai aktif
            $student->periods()->attach($period->id, [
                'id' => (string) Str::uuid(),
                'enrollment_date' => now(),
                'is_active' => true,
            ]);

            return true;
        } catch (\Exception $e) {
            // Bisa log error juga kalau mau
            \Log::error("Failed to register period: " . $e->getMessage());
            return false;
        }
    }
}