<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SettingSeeder::class);
        //$this->call(StudentSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(DivisionSeeder::class);
        $this->call(PresentationVenueSeeder::class);
        $this->call(ThesisTitleSeeder::class);
    }
}
