<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(['key' => 'app_name'], [
            'value' => 'Sistem Informasi Skripsi',
            'description' => 'Nama aplikasi yang ditampilkan di header.'
        ]);
    }
}