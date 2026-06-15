<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\User;
use App\Enums\Reports\ReportStatus;
use DomainException;

class UpdateReportStatusAction
{
    public function execute(
        Report $report,
        ReportStatus $newStatus,
        User $reviewer,
        ?string $remarks = null
    ): Report {

        if (!$report->status->canTransitionTo($newStatus)) {
            throw new DomainException(
                "Invalid transition: {$report->status->value} → {$newStatus->value}"
            );
        }

        $report->update([
            'status' => $newStatus,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'remarks' => $remarks,
        ]);

        return $report->refresh();
    }
}
