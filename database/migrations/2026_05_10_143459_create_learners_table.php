<?php

use App\Enums\Learners\EnrollmentStatus;
use App\Enums\Learners\LearnerType;
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
        Schema::create('learners', function (Blueprint $table) {

            // denormalized table
            // TODO: Improve later on
            $table->id();
            $table->uuid('uuid')->unique();

            // enrollment context
            $table->string('school_year', 9);
            $table->string('grade_to_enroll', 20);

            // type of student
            $table->enum('learner_type', LearnerType::values());

            // enrollment status
            $table->enum(
                'enrollment_status',
                EnrollmentStatus::values()
            )->default(EnrollmentStatus::Pending->value);

            // reason for being rejected, something like that
            $table->text('remarks')->nullable();

            // section assignment
            $table->foreignId('section_assignment_id')
                ->nullable()
                ->constrained('sections')
                ->nullOnDelete();

            // LRN
            $table->boolean('has_lrn')->default(false);
            $table->string('lrn', 20)->nullable()->unique();

            // personal information
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('name_extension', 10)->nullable();
            $table->date('birth_date');

            $table->enum('sex', Sex::values());

            // age is calculated from birth_date
            // remove later
            $table->unsignedTinyInteger('age');

            $table->string('mother_tongue', 100)->nullable();
            $table->string('religion')->nullable();
            $table->string('place_of_birth');

            // special groups
            $table->boolean('is_ip')->default(false);
            $table->string('ip_specification')->nullable();
            $table->boolean('is_4ps')->default(false);
            $table->string('household_id_number', 100)->nullable();
            $table->boolean('is_pwd')->default(false);
            $table->string('pwd_specification')->nullable();

            // address
            $table->string('house_no_street');
            $table->string('street_name');
            $table->string('barangay', 100);
            $table->string('municipality', 100);
            $table->string('province', 100);
            $table->string('country', 100);
            $table->string('zip_code', 10);

            // father
            $table->string('father_last_name', 100)->nullable();
            $table->string('father_first_name', 100)->nullable();
            $table->string('father_middle_name', 100)->nullable();
            $table->string('father_name_extension', 10)->nullable();

            // mother
            $table->string('mother_last_name', 100)->nullable();
            $table->string('mother_first_name', 100)->nullable();
            $table->string('mother_middle_name', 100)->nullable();
            $table->string('mother_name_extension', 10)->nullable();

            // contact number
            $table->string('contact_number');

            // OLD STUDENT INFO
            $table->string('last_grade_completed', 20);

            // TRANSFEREE: PREVIOUS SCHOOL
            $table->string('previous_school_name')->nullable();
            $table->text('previous_school_address')->nullable();
            $table->date('date_transferred')->nullable();

            // TRANSFEREE: SHS INFORMATION
            $table->string('shs_academic_track', 100)->nullable();
            $table->string('shs_strand', 100)->nullable();

            // CURRENT ACADEMIC ENROLLMENT
            $table->string('academic_track', 100)->nullable();
            $table->string('academic_strand', 100)->nullable();

            // consents
            $table->boolean('image_usage_consent')->default(false);
            $table->boolean('data_privacy_consent')->default(false);
            $table->dateTime('consented_at');

            // approval tracking
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learners');
    }
};
