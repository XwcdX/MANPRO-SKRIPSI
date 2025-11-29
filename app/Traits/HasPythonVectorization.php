<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HasPythonVectorization
{
    /**
     * Mengubah text menjadi vector embedding via Python Service.
     *
     * @param string $text
     * @return array|null
     */
    protected function getVectorFromPython(string $text): ?array
    {
        // Sebaiknya URL ini ditaruh di .env, tapi hardcode dulu gapapa sesuai request
        $baseUrl = env('PYTHON_API_URL', 'http://127.0.0.1:5001');

        try {
            $response = Http::timeout(5)->post("{$baseUrl}/vectorize", [
                'text' => $text
            ]);

            if ($response->successful()) {
                return $response->json()['vector'];
            }

            Log::error("Python Vectorize Failed: " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("Python Connection Error: " . $e->getMessage());
            return null;
        }
    }
}