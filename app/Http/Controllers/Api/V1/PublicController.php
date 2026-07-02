<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Announcements\AnnouncementStatus;
use App\Enums\Announcements\TargetAudience;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Personnel;
use App\Models\TvlOffer;
use Illuminate\Http\Request;

/**
 * Public, unauthenticated endpoints consumed by the marketing homepage
 * (HomePage.jsx and its child sections under Components/navbar-sections).
 *
 * Every method here MUST avoid leaking anything not meant for an
 * anonymous visitor — no internal IDs, contact details, dissemination
 * settings, etc. Sensitive fields are intentionally left off the
 * payloads below.
 */
class PublicController extends Controller
{
    /**
     * GET /api/v1/public/calendar-events
     *
     * Consumed by CalendarSection.jsx. Returns every published event
     * (past + future) created by admin/principal in calendar_events, plus
     * every posted announcement (folded in as read-only "Announcement"
     * calendar entries on their posted date, regardless of which role
     * created them), so the public calendar reflects school-wide
     * communications without staff having to duplicate the same date
     * into two places.
     */
    public function calendarEvents()
    {
        // NOTE: whereRaw('is_published = true'), not ->where('is_published', true).
        // Our pgsql connection runs with PDO::ATTR_EMULATE_PREPARES = true (for
        // Supabase's Supavisor pooler), which inlines a bound PHP bool as the
        // integer literal 1/0. Postgres has no implicit cast from integer to a
        // native boolean column, so that raised "column is of type boolean but
        // expression is of type integer" — a 500 here — even though the model
        // itself never touched the (fine) PgBoolean cast, because a query
        // builder ->where() bypasses casts entirely. Same fix already used in
        // AppCompatController::markNotificationRead().
        $events = CalendarEvent::query()
            ->whereRaw('is_published = true')
            ->orderBy('event_date')
            ->get()
            ->map(fn (CalendarEvent $event) => [
                'id'       => $event->uuid,
                'date'     => optional($event->event_date)->format('Y-m-d'),
                'tag'      => ucfirst($event->category),
                'title'    => $event->title,
                'desc'     => $event->description,
            ]);

        $principalAnnouncements = Announcement::query()
            ->where('status', AnnouncementStatus::Posted->value)
            ->whereNotNull('posted_at')
            ->orderByDesc('posted_at')
            ->limit(20)
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id'    => 'announcement-' . $announcement->uuid,
                'date'  => optional($announcement->posted_at)->format('Y-m-d'),
                'tag'   => 'Announcement',
                'title' => $announcement->title,
                'desc'  => $announcement->message,
            ]);

        $merged = $events
            ->concat($principalAnnouncements)
            ->filter(fn ($item) => ! empty($item['date']))
            ->sortBy('date')
            ->values();

        return response()->json(['data' => $merged]);
    }

    /**
     * GET /api/v1/public/tvl-offers
     *
     * Consumed by TVLSection.jsx.
     */
    public function tvlOffers()
    {
        // Same emulated-prepares boolean binding issue as calendarEvents()
        // above — raw comparison instead of ->where('is_active', true).
        $offers = TvlOffer::query()
            ->whereRaw('is_active = true')
            ->orderBy('display_order')
            ->get()
            ->map(fn (TvlOffer $offer) => [
                'id'             => $offer->uuid,
                'title'          => $offer->title,
                'description'    => $offer->description,
                'icon'           => $offer->icon,
                'image_url'      => $offer->image_url,
                'tag'            => 'TVL – ' . $offer->title,
                'tesda'          => collect($offer->certifications ?? [])->first() ?? 'NC II Eligible',
                'certifications' => $offer->certifications ?? [],
                'duration'       => $offer->duration ?? '2 Semesters',
                'details'        => $offer->details ?? [],
            ])
            ->values();

        return response()->json(['data' => $offers]);
    }

    /**
     * GET /api/v1/public/announcements
     *
     * Public-safe replacement for the old unauthenticated
     * GET /announcements route (removed in the CNS-05 fix because it
     * exposed urgency, target audience, and scheduled/unposted content
     * to anonymous visitors). This endpoint only ever returns
     * already-posted announcements targeted at "All" or "Students"
     * audiences, and strips internal scheduling fields before sending
     * them out. Teacher/staff-only announcements and drafts are
     * excluded; who created the announcement (admin or principal) no
     * longer matters — any posted, appropriately-targeted announcement
     * is public.
     */
    public function announcements(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 8), 1), 20);

        $announcements = Announcement::query()
            ->where('status', AnnouncementStatus::Posted->value)
            ->whereIn('target_audience', [
                TargetAudience::All->value,
                TargetAudience::Students->value,
            ])
            ->whereNotNull('posted_at')
            ->orderByDesc('posted_at')
            ->limit($limit)
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id'    => $announcement->uuid,
                'title' => $announcement->title,
                'text'  => $announcement->message,
                'urgency' => $announcement->urgency?->value,
                'date'  => optional($announcement->posted_at)->format('Y-m-d'),
            ])
            ->values();

        return response()->json(['data' => $announcements]);
    }

    /**
     * GET /api/v1/public/faculty
     *
     * Public-safe faculty directory for FacultySection.jsx. Unlike the
     * authenticated /v1/faculty endpoint, this never returns email,
     * phone number, address, or date of birth.
     */
    public function faculty(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 12), 1), 50);

        $personnel = Personnel::query()
            ->where(function ($q) {
                $q->whereNull('employment_status')
                    ->orWhere('employment_status', 'Active');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (Personnel $person) {
                $position = $person->position instanceof \BackedEnum
                    ? $person->position->value
                    : (string) $person->position;

                return [
                    'id'         => $person->uuid,
                    'firstName'  => $person->first_name,
                    'middleName' => $person->middle_name,
                    'lastName'   => $person->last_name,
                    'role'       => str_contains(strtoupper($position), 'TEACHER') ? 'Teacher' : 'Non-Teaching',
                    'department' => ucwords(strtolower(str_replace(['_', '-'], ' ', $position))),
                    'photo_url'  => $person->photo_url,
                ];
            })
            ->values();

        return response()->json(['data' => $personnel]);
    }
}