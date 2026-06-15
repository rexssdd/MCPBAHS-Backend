<?php

namespace App\Enums\Reports;

enum ReportStatus: string
{
    case ForAdminApproval    = 'for_admin_approval';
    case ForPrincipalApproval = 'for_principal_approval';
    case Approved            = 'approved';
    case Rejected            = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Human-readable label exposed as `status_label` in ReportResource.
     *
     * FIX: Previously both "for_admin_approval" and "for_principal_approval"
     * mapped to "Pending", making it impossible for the frontend (and users)
     * to distinguish which stage the report is at. Now they have distinct labels.
     *
     *   for_admin_approval     → "For Admin Approval"   (new — teacher submitted, admin has not acted)
     *   for_principal_approval → "For Principal Approval" (admin approved, principal must finalize)
     *   approved               → "Approved"
     *   rejected               → "Disapproved"           (matches existing frontend wording)
     *
     * The frontend STATUS_MAP in AdminReportsDocumentKit / PrincipalReports must be
     * updated to include these two new label strings (see fixes/STATUS_MAP note below).
     */
    public function label(): string
    {
        return match ($this) {
            self::ForAdminApproval     => 'For Admin Approval',
            self::ForPrincipalApproval => 'For Principal Approval',
            self::Approved             => 'Approved',
            self::Rejected             => 'Disapproved',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::ForAdminApproval    => [self::ForPrincipalApproval, self::Rejected],
            self::ForPrincipalApproval => [self::Approved, self::Rejected],
            self::Approved,
            self::Rejected            => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}