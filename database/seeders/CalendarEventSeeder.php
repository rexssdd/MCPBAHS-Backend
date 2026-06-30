<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CalendarEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the public "Activities & Events" calendar shown on the
     * homepage (CalendarSection.jsx). A handful of events are anchored
     * to the current month so the calendar never looks empty right
     * after a fresh deploy, plus a recurring set across the school year.
     */
    public function run(): void
    {
        $author = User::where('email', 'admin@gmail.com')->first() ?? User::first();

        $now = Carbon::now();

        $events = [
            ['title' => 'Enrollment for New School Year Now Open', 'category' => 'enrollment', 'days' => -20, 'desc' => "Online and walk-in enrollment for Grade 7 and Grade 11 is now open. Visit the Registrar's Office, Room 101, Monday–Friday, 8 AM–4 PM."],
            ['title' => 'Brigada Eskwela Schedule Released', 'category' => 'community', 'days' => -15, 'desc' => 'Volunteer clean-up and repair days are set for next month. All parents, alumni, and community members are welcome to join.'],
            ['title' => '4th Quarter Exam Schedule Posted', 'category' => 'academic', 'days' => -10, 'desc' => 'Fourth-quarter examinations will run for three days. Please review the subject schedule posted on the bulletin boards.'],
            ['title' => 'Parent-Teacher Conference', 'category' => 'advisory', 'days' => -5, 'desc' => 'Advisers will be available to discuss learner progress. Please check your child\'s section schedule.'],
            ['title' => 'Flag Ceremony & Orientation', 'category' => 'academic', 'days' => -2, 'desc' => 'All students must report in complete uniform for the school orientation and flag ceremony.'],
            ['title' => 'Faculty Development Day', 'category' => 'advisory', 'days' => 1, 'desc' => 'No classes — teachers will attend a training and development seminar.'],
            ['title' => 'Brigada Eskwela Clean-up Day', 'category' => 'community', 'days' => 4, 'desc' => 'Volunteer work begins. All parents, alumni, and community members may report to school at 7 AM.'],
            ['title' => 'Quarterly Examinations Begin', 'category' => 'academic', 'days' => 9, 'desc' => 'Examinations run for three days. Students must bring exam permits.'],
            ['title' => 'School Closed – Local Holiday', 'category' => 'holiday', 'days' => 13, 'desc' => 'Classes are suspended in observance of a local/national holiday. Regular classes resume the next school day.'],
            ['title' => 'Enrollment Deadline – Grade 7 & SHS', 'category' => 'enrollment', 'days' => 18, 'desc' => "Final day for submission of enrollment requirements at the Registrar's Office."],
            ['title' => 'Sports Fest Opening', 'category' => 'community', 'days' => 22, 'desc' => 'The annual intramurals kicks off with the parade of athletes and opening ceremony.'],
            ['title' => 'Report Card Distribution', 'category' => 'academic', 'days' => 27, 'desc' => 'Advisers will release quarterly report cards. Parents/guardians are encouraged to attend.'],
        ];

        foreach ($events as $event) {
            CalendarEvent::create([
                'uuid' => (string) Str::uuid(),
                'title' => $event['title'],
                'description' => $event['desc'],
                'event_date' => $now->copy()->addDays($event['days'])->toDateString(),
                'category' => $event['category'],
                // Intentionally omitted: 'is_published' => true.
                // Binding a PHP bool through PDO pgsql under emulated prepares
                // sends it as the bare literal `1`, and Postgres refuses to
                // implicitly cast an integer literal into a `boolean` column
                // ("column is of type boolean but expression is of type
                // integer"). The migration already defaults this column to
                // true, so just let the database apply it instead.
                'created_by' => $author?->id,
            ]);
        }
    }
}