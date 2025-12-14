<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PresentationVenue;

class PresentationVenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $venues = [
            [
                'name' => 'Lab JK',
                'location' => 'P.207a',
            ],
            [
                'name' => 'Lab SC',
                'location' => 'P.207b',
            ],
            [
                'name' => 'Lab MM',
                'location' => 'P.217',
            ],
            [
                'name' => 'Lab VR',
                'location' => 'P.217',
            ],
        ];

        foreach ($venues as $venue) {
            PresentationVenue::updateOrCreate(
                ['name' => $venue['name']],
                ['location' => $venue['location']]
            );
        }
    }
}
