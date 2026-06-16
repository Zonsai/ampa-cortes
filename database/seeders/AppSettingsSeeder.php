<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Services\AppSettings;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'ampa_name', 'value' => 'AMPA Cortés de Aragón', 'type' => 'text'],
            ['key' => 'school_name', 'value' => 'CEIP Cortés de Aragón', 'type' => 'text'],
            ['key' => 'primary_color', 'value' => '#4f46e5', 'type' => 'color'],
            ['key' => 'accent_color', 'value' => '#0f766e', 'type' => 'color'],
            ['key' => 'ampa_logo_path', 'value' => null, 'type' => 'image'],
            ['key' => 'school_logo_path', 'value' => null, 'type' => 'image'],
        ];

        foreach ($settings as $setting) {
            AppSetting::firstOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']],
            );
        }

        AppSettings::clearCache();
    }
}
