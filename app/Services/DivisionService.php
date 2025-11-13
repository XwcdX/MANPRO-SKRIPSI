<?php

namespace App\Services;

use App\Models\Division;

class DivisionService
{
    public function getAllDivisions()
    {
        return Division::orderBy('name')->get();
    }

    public function findDivision(string $id): ?Division
    {
        return Division::find($id);
    }

    public function createDivision(array $data): Division
    {
        return Division::create($data);
    }

    public function updateDivision(string $id, array $data): bool
    {
        $division = $this->findDivision($id);
        return $division ? $division->update($data) : false;
    }

    public function deleteDivision(string $id): bool
    {
        $division = $this->findDivision($id);
        if (!$division) {
            return false;
        }
        
        if ($division->lecturers()->count() > 0) {
            return false;
        }
        
        return $division->delete();
    }

    public function getLecturerCount(string $id): int
    {
        $division = $this->findDivision($id);
        return $division ? $division->lecturers()->count() : 0;
    }
}