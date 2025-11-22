<?php

namespace App\Services;

use App\Models\Lecturer;
use Illuminate\Support\Facades\Hash;

class LecturerService
{
    public function createLecturer(array $data): Lecturer
    {
        $lecturer = new Lecturer();
        $lecturer->name = $data['name'];
        $lecturer->email = $data['email'];
        $lecturer->primary_division_id = $data['primary_division_id'] ?? null;
        $lecturer->is_active = $data['is_active'] ?? true;
        $lecturer->password = Hash::make($data['password']);
        $lecturer->save();
        
        if (!empty($data['divisions'])) {
            $lecturer->divisions()->sync($data['divisions']);
        }
        
        return $lecturer;
    }

    public function updateLecturer(Lecturer $lecturer, array $data): Lecturer
    {
        $lecturer->name = $data['name'];
        $lecturer->email = $data['email'];
        $lecturer->primary_division_id = $data['primary_division_id'] ?? null;
        $lecturer->is_active = $data['is_active'] ?? true;
        
        if (!empty($data['password'])) {
            $lecturer->password = Hash::make($data['password']);
        }
        
        $lecturer->save();
        
        if (isset($data['divisions'])) {
            $lecturer->divisions()->sync($data['divisions']);
        }
        
        return $lecturer;
    }

    public function deleteLecturer(string $lecturerId): bool
    {
        $lecturer = Lecturer::find($lecturerId);
        if ($lecturer) {
            $lecturer->delete();
            return true;
        }
        return false;
    }

    public function assignDivisions(Lecturer $lecturer, array $divisionIds, ?string $primaryDivisionId = null): array
    {
        if ($primaryDivisionId && !in_array($primaryDivisionId, $divisionIds)) {
            return ['success' => false, 'message' => 'Primary division must be one of the selected divisions.'];
        }

        $lecturer->divisions()->sync($divisionIds);
        $lecturer->update(['primary_division_id' => $primaryDivisionId]);

        return ['success' => true, 'message' => 'Divisions assigned successfully.'];
    }
}
