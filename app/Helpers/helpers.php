<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Setting::updateOrCreate(
                    ['key' => $k],
                    ['value' => $v]
                );
                Cache::forget('setting.' . $k);
            }
            return null;
        }

        return Cache::rememberForever('setting.' . $key, function () use ($key, $default) {
            return Setting::find($key)->value ?? $default;
        });
    }
}