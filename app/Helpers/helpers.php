<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        return cache()->rememberForever(
            "setting_$key",
            function () use ($key, $default) {
                return Setting::where(
                    'key',
                    $key
                )->value('value') ?? $default;
            }
        );
    }
}