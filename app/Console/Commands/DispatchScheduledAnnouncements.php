<?php

namespace App\Console\Commands;

use App\Actions\Announcements\DispatchAnnouncementAction;
use App\Enums\Announcements\AnnouncementStatus;
use App\Models\Announcement;
use Illuminate\Console\Command;
use Throwable;

class DispatchScheduledAnnouncements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'announcements:dispatch-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch all scheduled announcements whose scheduled_at time has passed.';

    /**
     * Execute the console command.
     *
     * Runs every minute via the scheduler (see routes/console.php).
     * Finds every announcement in the Scheduled state whose scheduled_at is
     * in the past, then dispatches it through DispatchAnnouncementAction —
     * the same action used by the 'now' publish path. This ensures the two
     * paths are never out of sync.
     *
     * Each announcement is processed in a try/catch so one failure does not
     * block the rest. Failures are logged with enough context to identify
     * the problem announcement without re-querying.
     */
    public function handle(DispatchAnnouncementAction $dispatch): int
    {
        $due = Announcement::query()
            ->where('status', AnnouncementStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled announcements due.');
            return self::SUCCESS;
        }

        $this->info("Dispatching {$due->count()} scheduled announcement(s)…");

        $failed = 0;

        foreach ($due as $announcement) {
            try {
                // Mark as Processing first so a second scheduler tick that
                // runs before the jobs complete does not dispatch it again.
                $announcement->update(['status' => AnnouncementStatus::Processing]);

                $dispatch->execute($announcement);

                $this->line("  ✓ [{$announcement->uuid}] {$announcement->title}");
            } catch (Throwable $e) {
                $failed++;

                // Roll back to Scheduled so the next tick retries it.
                // If this persists, a human can investigate via the uuid.
                $announcement->update(['status' => AnnouncementStatus::Scheduled]);

                $this->error("  ✗ [{$announcement->uuid}] {$e->getMessage()}");

                report($e);
            }
        }

        $succeeded = $due->count() - $failed;
        $this->info("Done. {$succeeded} dispatched, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
