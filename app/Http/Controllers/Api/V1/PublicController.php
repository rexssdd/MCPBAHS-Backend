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
     * (past + future) so the homepage calendar can be paged month to
     * month without extra round trips.
     */
    public function calendarEvents()
    {
        $events = CalendarEvent::query()
            ->where('is_published', true)
            ->orderBy('event_date')
            ->get()
            ->map(fn (CalendarEvent $event) => [
                'id'       => $event->uuid,
                'date'     => optional($event->event_date)->format('Y-m-d'),
                'tag'      => ucfirst($event->category),
                'title'    => $event->title,
                'desc'     => $event->description,
            ])
            ->values();

        return response()->json(['data' => $events]);
    }

    /**
     * GET /api/v1/public/tvl-offers
     *
     * Consumed by TVLSection.jsx.
     */
    public function tvlOffers()
    {
        $offers = TvlOffer::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn (TvlOffer $offer) => [
                'id'             => $offer->uuid,
                'title'          => $offer->title,
                'description'    => $offer->description,
                'icon'           => $offer->icon,
                'tag'            => 'TVL – ' . $offer->title,
                'tesda'          => collect($offer->certifications ?? [])->first() ?? 'NC II Eligible',
                'certifications' => $offer->certifications ?? [],
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
     * already-posted, "all audience" announcements, and strips
     * internal scheduling fields before sending them out.
     */
    public function announcements(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 8), 1), 20);

        $announcements = Announcement::query()
            ->where('status', AnnouncementStatus::Posted->value)
            ->where('target_audience', TargetAudience::All->value)
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
                    'photo_url'  => null,
                ];
            })
            ->values();

        return response()->json(['data' => $personnel]);
    }
}
