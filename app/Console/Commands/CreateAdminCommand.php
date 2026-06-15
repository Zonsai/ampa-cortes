<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateAdminCommand extends Command
{
    protected $signature = 'ampa:create-admin
                            {--email= : Email del administrador}
                            {--name= : Nombre del administrador}
                            {--password= : Contraseña (si se omite, se solicitará de forma segura)}
                            {--force : Actualizar nombre, contraseña y rol si el usuario ya existe}';

    protected $description = 'Crea o actualiza un usuario administrador con rol super_admin';

    public function handle(): int
    {
        if (! Role::where('name', 'super_admin')->exists()) {
            $this->error('El rol super_admin no existe. Ejecuta primero: php artisan db:seed --class=RoleSeeder --force');

            return self::FAILURE;
        }

        $email = $this->option('email') ?? $this->ask('Email del administrador');
        $name = $this->option('name') ?? $this->ask('Nombre del administrador');
        $password = $this->option('password') ?? $this->secret('Contraseña (mínimo 8 caracteres)');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email:filter'],
                'name' => ['required', 'string', 'min:2', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing && ! $this->option('force')) {
            $this->warn("Ya existe un usuario con el email {$email}.");
            $this->warn('Usa --force para actualizar su nombre, contraseña y rol.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }

        $action = $existing ? 'actualizado' : 'creado';
        $this->info("Administrador {$action} correctamente: {$email}");

        return self::SUCCESS;
    }
}
