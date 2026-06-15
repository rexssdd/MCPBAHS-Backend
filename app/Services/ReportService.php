<?php

namespace App\Services;

use App\Actions\Reports\StoreReportAction;
use App\Actions\Reports\CreateReportAction;
use App\Actions\Reports\DeleteReportAction;
use App\Actions\Reports\UpdateReportStatusAction;
use App\Models\Report;
use App\Models\User;
use App\Models\Notification;
use App\Enums\Reports\ReportStatus;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        private CreateReportAction $createReport,
        private DeleteReportAction $deleteFile,
        private UpdateReportStatusAction $updateStatus,
        private StoreReportAction $storeReport
    ) {}

    /* ───────────────────────── ACTIVE LIST ───────────────────────── */

    public function index()
    {
        return Report::with('submitter', 'reviewer')
            ->active()
            ->latest()
            ->paginate();
    }

    public function indexArchived()
    {
        return Report::with('submitter', 'reviewer')
            ->archived()
            ->latest('archived_at')
            ->paginate();
    }

    public function show(Report $report)
    {
        return $report;
    }

    /* ───────────────────────── STORE ───────────────────────── */

    public function store(array $data, UploadedFile $file, User $user): Report
    {
        return DB::transaction(
            fn() => $this->createReport->execute($data, $file, $user)
        );
    }

    /* ───────────────────────── UPDATE ───────────────────────── */

    public function update(Report $report, array $data, ?UploadedFile $file = null): Report
    {
        $scalarData = array_diff_key($data, array_flip(['file']));

        return DB::transaction(function () use ($report, $scalarData, $file) {
            if ($file) {
                $fileData = $this->storeReport->execute($file, $report->uuid);
                $report->update($fileData);
            }

            $report->update($scalarData);

            return $report->refresh();
        });
    }

    /* ───────────────────────── DELETE ───────────────────────── */

    public function delete(Report $report): void
    {
        $filePath = $report->file_path;

        DB::transaction(function () use ($report, $filePath) {
            $report->delete();

            DB::afterCommit(function () use ($filePath) {
                if ($filePath) {
                    $this->deleteFile->execute($filePath);
                }
            });
        });
    }

    /* ───────────────────────── ARCHIVE ───────────────────────── */

    public function archive(Report $report, User $user): Report
    {
        abort_if($report->isArchived(), 422, 'Report is already archived.');

        $report->update([
            'archived_at' => now(),
            'archived_by' => $user->id,
        ]);

        return $report->refresh();
    }

    public function unarchive(Report $report): Report
    {
        abort_unless($report->isArchived(), 422, 'Report is not archived.');

        $report->update([
            'archived_at' => null,
            'archived_by' => null,
        ]);

        return $report->refresh();
    }

    /* ───────────────────────── APPROVAL FLOW ───────────────────────── */

    /**
     * Approve a report.
     *
     * FIX: The old code checked `$report->status === ReportStatus::Pending`
     * but that enum case does not exist — the correct case is `ForAdminApproval`.
     * Also uses $user->getRoleNames()->first() (Spatie) instead of $user->role
     * because the project uses Spatie permissions (no plain `role` column).
     */
    public function approve(Report $report, User $user, ?string $remarks): Report
    {
        return DB::transaction(function () use ($report, $user, $remarks) {

            if ($report->isArchived()) {
                throw new DomainException('Archived reports cannot be approved.');
            }

            if (in_array($report->status, [ReportStatus::Approved, ReportStatus::Rejected], true)) {
                throw new DomainException('Report already finalized.');
            }

            // Resolve role via Spatie (hasRole) rather than a plain ->role attribute
            // that many setups don't have as a fillable column.
            $isAdmin      = $user->hasRole('admin');
            $isPrincipal  = $user->hasRole('principal');

            if ($isAdmin) {
                // Admin can only forward reports that are still awaiting admin review.
                if ($report->status !== ReportStatus::ForAdminApproval) {
                    throw new DomainException(
                        'Admin can only approve reports that are awaiting admin approval.'
                    );
                }
                $nextStatus = ReportStatus::ForPrincipalApproval;

            } elseif ($isPrincipal) {
                // Principal can only give final approval to reports forwarded by admin.
                if ($report->status !== ReportStatus::ForPrincipalApproval) {
                    throw new DomainException(
                        'Principal can only approve reports that are awaiting principal approval.'
                    );
                }
                $nextStatus = ReportStatus::Approved;

            } else {
                throw new DomainException('Unauthorized approver role.');
            }

            $updated = $this->updateStatus->execute($report, $nextStatus, $user, $remarks);

            // ── Notify the submitting teacher ──
            $statusLabel = $nextStatus === ReportStatus::ForPrincipalApproval
                ? 'forwarded to the Principal for final approval'
                : 'approved';

            Notification::create([
                'user_id' => $report->submitted_by,
                'type'    => 'report_approved',
                'title'   => 'Report ' . ucfirst($statusLabel),
                'message' => "Your report ({$report->uuid}) has been {$statusLabel}."
                    . ($remarks ? " Remarks: {$remarks}" : ''),
                'report_uuid' => $report->uuid,
            ]);

            // ── Notify the principal when admin forwards ──
            if ($nextStatus === ReportStatus::ForPrincipalApproval) {
                $principals = User::role('principal')->get();
                foreach ($principals as $principal) {
                    Notification::create([
                        'user_id'     => $principal->id,
                        'type'        => 'report_pending_principal',
                        'title'       => 'Report Awaiting Your Approval',
                        'message'     => "A report ({$report->uuid}) has been forwarded by admin and requires your final approval.",
                        'report_uuid' => $report->uuid,
                    ]);
                }
            }

            return $updated;
        });
    }

    public function reject(Report $report, User $user, ?string $remarks): Report
    {
        return DB::transaction(function () use ($report, $user, $remarks) {

            if (! $user->hasAnyRole(['admin', 'principal'])) {
                throw new DomainException('Unauthorized approver role.');
            }

            if (in_array($report->status, [ReportStatus::Approved, ReportStatus::Rejected], true)) {
                throw new DomainException('Report already finalized.');
            }

            $updated = $this->updateStatus->execute(
                $report,
                ReportStatus::Rejected,
                $user,
                $remarks
            );

            $roleLabel = $user->hasRole('principal') ? 'Principal' : 'Admin';

            Notification::create([
                'user_id'     => $report->submitted_by,
                'type'        => 'report_rejected',
                'title'       => 'Report Rejected',
                'message'     => "Your report ({$report->uuid}) has been rejected by the {$roleLabel}."
                    . ($remarks ? " Reason: {$remarks}" : ''),
                'report_uuid' => $report->uuid,
            ]);

            return $updated;
        });
    }
}