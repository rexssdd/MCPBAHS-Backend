<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backs the public homepage "Activities & Events" calendar
     * (CalendarSection.jsx). Previously the frontend fetched a
     * relative `/api/calendar-events` path that did not exist on the
     * backend at all, so the page always fell back to hard-coded
     * sample dates and showed "Could not load latest events."
     */
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('title');
            $table->text('description')->nullable();

            // The calendar date the event appears on (YYYY-MM-DD).
            $table->date('event_date');

            // Matches the legend used by CalendarSection.jsx:
            // Enrollment / Academic / Community / Holiday / Advisory.
            $table->enum('category', [
                'enrollment',
                'academic',
                'community',
                'holiday',
                'advisory',
            ])->default('advisory');

            // Only published events are returned by the public endpoint,
            // so staff can stage future events without showing them yet.
            $table->boolean('is_published')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['event_date', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
