<?php

use App\Enums\Sections\GradeLevel;
use App\Enums\Sections\AcademicStrand;
use App\Enums\Sections\AcademicTrack;
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
        Schema::create('sections', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('section_name', 100);

            $table->enum('grade_level', GradeLevel::values());

            $table->string('school_year', 20);

            $table->enum(
                'academic_track',
                AcademicTrack::values()
            )->nullable();

            $table->enum(
                'academic_strand',
                AcademicStrand::values()
            )->nullable();

            $table->foreignId('adviser_id')
                ->nullable()
                ->constrained('personnels')
                ->nullOnDelete();

            $table->unique([
                'section_name',
                'grade_level',
                'school_year'
            ]);

            $table->index(['school_year']);
            $table->index(['grade_level']);
            $table->index(['adviser_id']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
