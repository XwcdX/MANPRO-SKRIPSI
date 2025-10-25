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
        return Lecturer::with('roles', 'division')->get();
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
        $roleName = $lecturer->roles->first()->name ?? 'Supervisor';
        $divisionName = optional($lecturer->division)->name ?? '';

        return [
            $lecturer->name,
            $lecturer->email,
            $roleName,
            $divisionName,
            '',
        ];
    }
}