<?php

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
        Schema::create('class_schedules', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('room_no', 100)->nullable();
            $table->string('subject', 255);
            $table->string('school_year', 20);

            $table->enum('semester', [
                '1st',
                '2nd'
            ])->nullable();

            $table->json('days');
            $table->time('start_time');
            $table->time('end_time');

            $table->foreignId('section_id')
                ->nullable()
                ->constrained('sections')
                ->nullOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('personnels')
                ->nullOnDelete();

            $table->index(['teacher_id', 'start_time', 'end_time']);
            $table->index(['section_id', 'school_year', 'semester']);
            $table->index(['room_no', 'school_year', 'semester']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
