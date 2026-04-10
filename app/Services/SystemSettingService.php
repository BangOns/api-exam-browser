<?php

namespace App\Services;

use App\Models\SystemSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemSettingService
{
    public function get(string $key, $default = null)
    {
        return Cache::remember("setting_$key", 60, function () use ($key, $default) {
            $setting = SystemSettings::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public function set(string $key, array $value)
    {
        $setting = DB::transaction(function () use ($key, $value) {
            $setting = SystemSettings::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
            $setting = $setting->fresh();
            return $setting;
        });

        Cache::forget("setting_$key");

        return $setting;
    }
}
