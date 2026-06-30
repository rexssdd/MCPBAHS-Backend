<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CalendarEventResource;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;

/**
 * Admin + principal management of the public homepage events calendar
 * (CalendarSection.jsx). Restricted to 'role:admin|principal' in
 * routes/api.php — the public, read-only listing lives in
 * PublicController::calendarEvents(), which also folds in principal
 * announcements as informational calendar entries.
 */
class CalendarEventController extends Controller
{
    public function index(Request $request)
    {
        $events = CalendarEvent::query()
            ->with('creator:id,name')
            ->orderByDesc('event_date')
            ->paginate(15);

        return CalendarEventResource::collection($events);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['required', 'date'],
            'category' => ['required', 'in:enrollment,academic,community,holiday,advisory'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $data['created_by'] = $request->user()->id;

        $event = CalendarEvent::create($data);

        return response()->json([
            'message' => 'Event created successfully.',
            'data' => new CalendarEventResource($event),
        ], 201);
    }

    public function show(CalendarEvent $calendar_event)
    {
        return new CalendarEventResource($calendar_event->load('creator:id,name'));
    }

    public function update(Request $request, CalendarEvent $calendar_event)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['sometimes', 'date'],
            'category' => ['sometimes', 'in:enrollment,academic,community,holiday,advisory'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $calendar_event->update($data);

        return response()->json([
            'message' => 'Event updated successfully.',
            'data' => new CalendarEventResource($calendar_event->refresh()),
        ]);
    }

    public function destroy(CalendarEvent $calendar_event)
    {
        $calendar_event->delete();

        return response()->noContent();
    }
}
