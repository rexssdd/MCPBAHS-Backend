<?php

use App\Enums\Announcements\AnnouncementStatus;
use App\Enums\Announcements\AnnouncementUrgency;
use App\Enums\Announcements\DisseminationMode;
use App\Enums\Announcements\TargetAudience;
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
        Schema::create('announcements', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('title');
            $table->text('message');

            $table->enum(
                'urgency',
                AnnouncementUrgency::values()
            )->default(AnnouncementUrgency::Normal->value);

            $table->json('dissemination_modes');
            // ['sms', 'in-app', 'email']

            $table->enum(
                'target_audience',
                TargetAudience::values()
            );

            $table->timestamp('scheduled_at')->nullable();

            $table->enum(
                'status',
                AnnouncementStatus::values()
            )->default(AnnouncementStatus::Draft->value);

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('posted_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
