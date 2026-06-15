<?php

use App\Enums\Personnel\EmploymentStatus;
use App\Enums\Personnel\PersonnelDepartment;
use App\Enums\Personnel\PersonnelPosition;
use App\Enums\Sex;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personnels', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('personnel_id_number')->unique();

            $table->string('first_name', 255);
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 100);

            $table->string('email')->unique();
            $table->string('phone_number')->unique();

            $table->date('date_of_birth');

            $table->enum('sex', Sex::values());

            $table->string('country', 100);
            $table->string('region', 100);
            $table->string('province', 100);
            $table->string('brgy_street_address', 100);
            $table->string('city', 100);
            $table->string('postal_code', 20);

            $table->integer('teaching_load');
            $table->enum('department', PersonnelDepartment::values());

            $table->enum('position', PersonnelPosition::values());

            $table->enum(
                'employment_status',
                EmploymentStatus::values()
            )->default(EmploymentStatus::Active->value);

            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->index()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
            // $table->foreignId('advisory_class')
            //     ->nullable()
            //     ->constrained('sections')
            //     ->nullOnDelete();

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnels');
    }
};
