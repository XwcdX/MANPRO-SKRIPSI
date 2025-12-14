<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'name' => 'AI',
                'description' => 'Artificial Intelligence',
            ],
            [
                'name' => 'BIS',
                'description' => 'Business Information System',
            ],
            [
                'name' => 'DSA',
                'description' => 'Data Science and Analytics',
            ],
            [
                'name' => 'GD',
                'description' => 'Game Development',
            ],
            [
                'name' => 'CS',
                'description' => 'Cyber Security',
            ],
            [
                'name' => 'FS',
                'description' => 'Full Stack Development',
            ],
        ];

        foreach ($divisions as $division) {
            Division::updateOrCreate(
                ['name' => $division['name']],
                ['description' => $division['description']]
            );
        }
    }
}
