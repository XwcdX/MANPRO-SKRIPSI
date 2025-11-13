<?php

namespace App\Services;

use App\Models\Lecturer;
use App\Models\Division;
use Illuminate\Support\Facades\Hash;

class LecturerService
{
    public function getLecturers(?string $search = null)
    {
        return Lecturer::with(['division', 'roles'])
            ->when($search, function ($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            });
    }

    public function createLecturer(array $data): Lecturer
    {
        $data['password'] = Hash::make($data['password']);
        $data['division_id'] = $data['division_id'] ?: null;
        
        return Lecturer::create($data);
    }

    public function updateLecturer(Lecturer $lecturer, array $data): Lecturer
    {
        $lecturer->name = $data['name'];
        $lecturer->email = $data['email'];
        $lecturer->title = $data['title'];
        $lecturer->division_id = $data['division_id'] ?: null;
        $lecturer->is_active = $data['is_active'];

        if (!empty($data['password'])) {
            $lecturer->password = Hash::make($data['password']);
        }

        $lecturer->save();
        
        return $lecturer;
    }

    public function deleteLecturer(Lecturer $lecturer): bool
    {
        return $lecturer->delete();
    }

    public function getDivisions()
    {
        return Division::orderBy('name')->get();
    }
}
