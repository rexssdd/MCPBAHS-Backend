<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Announcements\AnnouncementStatus;
use App\Enums\Announcements\AnnouncementUrgency;
use App\Enums\Announcements\DisseminationMode;
use App\Enums\Announcements\TargetAudience;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcements\StoreAnnouncementRequest;
use App\Http\Requests\Announcements\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Services\Announcements\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $service
    )
    {
  
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return AnnouncementResource::collection(
            Announcement::with('creator')
                ->latest()
                ->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAnnouncementRequest $request)
    {
        $announcement = $this->service->create(
            $request->validated()
        );

        // CNS-BE-04 fix: load the creator relationship before returning so
        // AnnouncementResource::whenLoaded('creator') returns actual data
        // instead of null on every POST response.
        return response()->json(
            new AnnouncementResource($announcement->load('creator')),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Announcement $announcement)
    {
        return new AnnouncementResource(
            $announcement->load('creator')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateAnnouncementRequest $request,
        Announcement $announcement
    ) {
        $announcement = $this->service->update(
            $announcement,
            $request->validated()
        );

        return new AnnouncementResource($announcement);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $this->service->delete($announcement);

        return response()->noContent();
    }


    // archive and restore, for later use
    public function archived()
    {
        // Previously returned a raw response()->json() while every other endpoint
        // wraps results in AnnouncementResource::collection(). This made the
        // archived list return a different JSON shape, breaking any frontend that
        // expected a consistent structure. Using the resource collection ensures
        // the same field names, casts, and relationships on all list endpoints.
        return AnnouncementResource::collection(
            $this->service->archived()
        );
    }

    public function restore(Announcement $announcement)
    {
        $this->service->restore($announcement);

        return response()->noContent();
    }

    public function forceDelete(Announcement $announcement)
    {
        $this->service->forceDelete($announcement);

        return response()->noContent();
    }

    /**
     * Bulk soft-delete announcements by UUID.
     *
     * CNS-BULK fix: the frontend's bulkDeleteAnnouncements() calls
     * DELETE /announcements/bulk with { ids: uuid[] }. Without this route
     * the request always 404s, causing the fallback to fire N sequential
     * single-delete requests instead — visible as a slow multi-request
     * waterfall in devtools.
     */
    public function bulkDestroy(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'uuid'],
        ]);

        $deleted = $this->service->bulkDelete($request->input('ids'));

        return response()->json(['deleted' => $deleted]);
    }
}