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
            $lecturer = Lecturer::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => isset($row['password']) ? Hash::make($row['password']) : Hash::make('password')
                ]
            );

            if ($lecturer->wasRecentlyCreated === false) {
                $lecturer->name = $row['name'];
            }
            
            if (!empty($row['division'])) {
                $division = Division::where('name', $row['division'])->first();
                $lecturer->division_id = $division->id ?? null;
            } else {
                $lecturer->division_id = null;
            }

            $lecturer->save();

            $roleName = $row['role'] ?? 'Supervisor';
            $role = Role::where('name', $roleName)->where('guard_name', 'lecturer')->first();
            
            if (!$role) {
                $role = Role::where('name', 'Supervisor')->where('guard_name', 'lecturer')->first();
            }

            if ($role) {
                $lecturer->syncRoles([$role->name]);
            }
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