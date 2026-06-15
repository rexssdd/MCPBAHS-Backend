<?php

use App\Enums\Reports\ReportStatus;
use App\Enums\Reports\ReportType;
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
        Schema::create('reports', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();

            $table->enum(
                'form_type',
                ReportType::values()
            );

            $table->string('school_year', 20);
            $table->string('file_path', 255);
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->enum(
                'status',
                ReportStatus::values()
            )->default(ReportStatus::ForAdminApproval->value);

            $table->text('remarks')->nullable();

            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->index(['status', 'form_type', 'school_year']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
