<?php

namespace App\Imports;

use App\Models\Division;
use App\Models\Lecturer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Spatie\Permission\Models\Role;

class LecturersImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $lecturer = Lecturer::firstOrNew(['email' => $row['email']]);
            
            $lecturer->name = $row['name'];
            
            if (!empty($row['password'])) {
                $lecturer->password = Hash::make($row['password']);
            } elseif (!$lecturer->exists) {
                $emailUsername = explode('@', $row['email'])[0];
                $lecturer->password = Hash::make($emailUsername . 'password');
            }
            
            $lecturer->save();
            
            // Handle multiple divisions
            if (!empty($row['division'])) {
                $divisionNames = array_map('trim', explode(',', $row['division']));
                $divisionIds = Division::whereIn('name', $divisionNames)->pluck('id')->toArray();
                $lecturer->divisions()->sync($divisionIds);
            } else {
                $lecturer->divisions()->sync([]);
            }

            $roleNames = !empty($row['role']) ? array_map('trim', explode(',', $row['role'])) : ['Supervisor'];
            $validRoles = [];
            
            foreach ($roleNames as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'lecturer')->first();
                if ($role) {
                    $validRoles[] = $role->name;
                }
            }
            
            if (empty($validRoles)) {
                $validRoles = ['Supervisor'];
            }

            $lecturer->syncRoles($validRoles);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'password' => 'nullable|string',
            'role' => 'nullable|string',
            'division' => 'nullable|string|exists:divisions,name',
        ];
    }
}