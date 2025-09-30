<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = [
            [
                'name' => 'Terry Clement',
                'email' => 'c14230074@john.petra.ac.id',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'thesis_title' => 'AI-based Recommendation System',
                'status' => 1,
                'head_division_comment' => 'Good topic, needs refinement.',
                'revision_notes' => null,
                'final_thesis_path' => null,
                'due_date' => now()->addMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bob Williams',
                'email' => 'bob@john.petra.ac.id',
                'password' => Hash::make('password'),
                'email_verified_at' => null,
                'thesis_title' => null,
                'status' => 0,
                'head_division_comment' => null,
                'revision_notes' => null,
                'final_thesis_path' => null,
                'due_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@john.petra.ac.id',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'thesis_title' => 'Blockchain for E-Voting',
                'status' => 3,
                'head_division_comment' => 'Forwarded to committee.',
                'revision_notes' => 'Add more references.',
                'final_thesis_path' => null,
                'due_date' => now()->addMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach($students as $student){
            Student::updateOrCreate($student);
        }
    }
}
