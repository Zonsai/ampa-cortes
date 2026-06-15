<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Safe to run in any environment — idempotent, no credentials.
        $this->call([
            RoleSeeder::class,
            AcademicYearSeeder::class,
        ]);

        // Local-only seeders: demo users with known passwords, sample data.
        // NEVER run these in production.
        if (app()->environment('local')) {
            $this->call([
                AdminUserSeeder::class,
                DemoDataSeeder::class,
            ]);
        }
    }
}
