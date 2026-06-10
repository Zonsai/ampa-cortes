<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::updateOrCreate(
            ['name' => '2024-2025'],
            ['starts_at' => '2024-09-09', 'ends_at' => '2025-06-20', 'is_active' => false]
        );

        AcademicYear::updateOrCreate(
            ['name' => '2025-2026'],
            ['starts_at' => '2025-09-08', 'ends_at' => '2026-06-19', 'is_active' => true]
        );
    }
}
