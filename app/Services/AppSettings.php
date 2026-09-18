<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AppSettings
{
    private const CACHE_KEY = 'app_settings';

    private const CACHE_TTL = 3600;

    /** @var array<string, string|null> */
    private static array $defaults = [
        'ampa_name' => 'AMPA Cortés de Aragón',
        'school_name' => 'CEIP Cortés de Aragón',
        'primary_color' => '#245b63',
        'accent_color' => '#4f7c70',
        'ampa_logo_path' => null,
        'school_logo_path' => null,
    ];

    public static function get(string $key): mixed
    {
        return static::all()[$key] ?? static::$defaults[$key] ?? null;
    }

    /** @return array<string, string|null> */
    public static function all(): array
    {
        return Cache::remember(static::CACHE_KEY, static::CACHE_TTL, function () {
            $stored = AppSetting::pluck('value', 'key')->toArray();

            return array_merge(static::$defaults, $stored);
        });
    }

    /** @return array<string, string|null> */
    public static function defaults(): array
    {
        return static::$defaults;
    }

    public static function set(string $key, mixed $value): void
    {
        AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        static::clearCache();
    }

    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    public static function logoUrl(string $key): ?string
    {
        $path = static::get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
