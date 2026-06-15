<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SystemAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Admin',
                'email' => 'admin@gmail.com',
                'role' => 'admin',
                'password' => '1234',
            ],
            [
                'name' => 'Principal User',
                'email' => 'principal@gmail.com',
                'role' => 'principal',
                'password' => '1234',
            ],
            [
                'name' => 'Registrar User',
                'email' => 'registrar@gmail.com',
                'role' => 'registrar',
                'password' => '1234',
            ],
            [
                'name' => 'Teacher User',
                'email' => 'teacher@gmail.com',
                'role' => 'teacher',
                'password' => '1234',
            ],
        ];

        foreach ($users as $user) {
            $model = User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $user['name'],

                    'password' => Hash::make($user['password']),
                    'account_status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            $model->update([
                'role' => $user['role']
            ]);

            if (method_exists($model, 'syncRoles')) {
                $model->syncRoles($user['role']);
            }
        }
    }
}
