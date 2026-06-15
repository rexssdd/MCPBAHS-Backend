<?php

namespace Database\Seeders;

use App\Enums\Personnel\PersonnelDepartment;
use App\Models\Personnel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Enums\Sex;
use App\Enums\Personnel\PersonnelPosition;
use App\Enums\Personnel\EmploymentStatus;

class PersonnelSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        DB::table('personnels')->delete();

        foreach (range(1, 30) as $i) {
            Personnel::create([
                'uuid' => (string) Str::uuid(),
                'personnel_id_number' => $faker->unique()->numberBetween(100000, 999999),

                'first_name' => $faker->firstName(),
                'middle_name' => $faker->optional()->firstName(),
                'last_name' => $faker->lastName(),

                'email' => $faker->unique()->safeEmail(),
                'phone_number' => $faker->unique()->phoneNumber(),

                'date_of_birth' => $faker->date('Y-m-d', '-25 years'),

                'sex' => $faker->randomElement(Sex::cases()),

                'country' => 'Philippines',
                'region' => $faker->randomElement(['Davao Region', 'NCR', 'CALABARZON']),
                'province' => $faker->state(),
                'brgy_street_address' => $faker->streetAddress(),
                'city' => $faker->city(),
                'postal_code' => $faker->postcode(),

                'teaching_load' => $faker->numberBetween(6, 24),

                'position' => $faker->randomElement(PersonnelPosition::cases()),
                'department' => $faker->randomElement(PersonnelDepartment::cases()),

                'employment_status' => $faker->randomElement(
                    EmploymentStatus::cases()
                ),
            ]);
        }
    }
}
