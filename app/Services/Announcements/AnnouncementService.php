<?php

namespace App\Services\Announcements;

use App\Actions\Announcements\CreateAnnouncementAction;
use App\Actions\Announcements\DispatchAnnouncementAction;
use App\Actions\Announcements\UpdateAnnouncementAction;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AnnouncementService
{
    public function __construct(
        protected CreateAnnouncementAction $createAction,
        protected UpdateAnnouncementAction $updateAction,
        protected DispatchAnnouncementAction $dispatchAction,
    ) {
        //
    }

    public function create(array $data): Announcement
    {
        /** @var User $user */
        $user = Auth::user();

        $data['created_by'] = $user->id;

        $announcement = $this->createAction->execute($data);

        if (($data['publish_mode'] ?? '') === 'now') {
            $this->dispatchAction->execute($announcement);
        }

        return $announcement;
    }

public function update(
        Announcement $announcement,
        array $data
    ): Announcement {
        $announcement = $this->updateAction->execute(
            $announcement,
            $data
        );

        // BUG FIX: without this, an announcement edited to publish_mode
        // "now" was left at status "processing" forever — nothing ever
        // ran DispatchAnnouncementAction to send it out and flip it to
        // "posted". Mirrors the same check already done in create().
        if (($data['publish_mode'] ?? '') === 'now') {
            $this->dispatchAction->execute($announcement);
        }

        return $announcement;
    }
    public function delete(Announcement $announcement): void
    {
        $announcement->delete();
    }

    public function archived()
    {
        return Announcement::onlyTrashed()
            ->latest()
            ->paginate();
    }

    public function restore(Announcement $announcement): void
    {
        $announcement->restore();
    }

    public function forceDelete(Announcement $announcement): void
    {
        $announcement->forceDelete();
    }

    /**
     * Bulk soft-delete announcements by UUID array.
     * Returns the count of actually deleted records.
     */
    public function bulkDelete(array $uuids): int
    {
        return Announcement::whereIn('uuid', $uuids)->delete();
    }

    public function dispatch(Announcement $announcement): void
    {
        $this->dispatchAction->execute($announcement);
    }
}