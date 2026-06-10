<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Families
            'view families', 'create families', 'edit families', 'delete families',
            // Guardians
            'view guardians', 'create guardians', 'edit guardians', 'delete guardians',
            // Students
            'view students', 'create students', 'edit students', 'delete students',
            // Reports
            'view reports',
            // Settings (AcademicYear, SchoolStage, Grade, Classroom)
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $juntaAmpa = Role::firstOrCreate(['name' => 'junta_ampa']);
        $adminExtraescolares = Role::firstOrCreate(['name' => 'admin_extraescolares']);
        $adminFormularios = Role::firstOrCreate(['name' => 'admin_formularios']);
        Role::firstOrCreate(['name' => 'familia']);

        // junta_ampa: full CRUD on all Phase 1 entities + settings
        $juntaAmpa->syncPermissions(Permission::all());

        // admin_extraescolares: read-only in Phase 1
        $adminExtraescolares->syncPermissions([
            'view families',
            'view guardians',
            'view students',
            'view reports',
        ]);

        // admin_formularios: read-only on people entities
        $adminFormularios->syncPermissions([
            'view families',
            'view guardians',
            'view students',
        ]);

        // familia: no permissions in Phase 1 (portal familiar is future scope)
    }
}
