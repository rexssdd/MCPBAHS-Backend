<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [

            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            'students.view',
            'students.create',
            'students.update',
            'students.delete',

            'grades.view',
            'grades.create',
            'grades.update',

            'subjects.view',
            'subjects.create',
            'subjects.update',
            'subjects.delete',

            'reports.view',
            'reports.generate',
        ];

        foreach ($permissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'admin',
        ]);

        $principal = Role::firstOrCreate([
            'name' => 'principal',
        ]);

        $registrar = Role::firstOrCreate([
            'name' => 'registrar',
        ]);

        $teacher = Role::firstOrCreate([
            'name' => 'teacher',
        ]);

        $admin->givePermissionTo(Permission::all());

        $principal->givePermissionTo([
            'students.view',
            'grades.view',
            'reports.view',
            'reports.generate',
        ]);

        $registrar->givePermissionTo([
            'students.view',
            'students.create',
            'students.update',
            'subjects.view',
        ]);

        $teacher->givePermissionTo([
            'students.view',
            'grades.view',
            'grades.create',
            'grades.update',
        ]);

    }
}
