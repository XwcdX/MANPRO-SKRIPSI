<?php

namespace App\Exports;

use App\Models\Lecturer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LecturersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Lecturer::with('roles', 'divisions')->get();
    }

    public function headings(): array
    {
        return [
            'name',
            'email',
            'role',
            'division',
            'password',
        ];
    }


    public function map($lecturer): array
    {
        $roleNames = $lecturer->roles->pluck('name')->implode(', ') ?: 'Supervisor';
        $divisionNames = $lecturer->divisions->pluck('name')->implode(', ');

        return [
            $lecturer->name,
            $lecturer->email,
            $roleNames,
            $divisionNames,
            '',
        ];
    }
}