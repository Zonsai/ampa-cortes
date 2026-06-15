<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('AdminUserSeeder solo puede ejecutarse en entorno local. Para crear el primer administrador en producción usa: php artisan ampa:create-admin');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => 'admin@ampa.test'],
            [
                'name' => 'Administrador AMPA',
                'email' => 'admin@ampa.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole('super_admin');
    }
}
