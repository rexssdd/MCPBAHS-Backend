<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Admin + Principal + Registrar → full access
     * Teacher → only own reports
     */
    public function download(?User $user, Report $report): bool
    {
        if (!$user) {
            return false;
        }

        // Full access roles (registrar added — was missing, causing 403 on every download)
        if ($user->hasAnyRole(['admin', 'principal', 'registrar'])) {
            return true;
        }

        // Teacher restriction
        if ($user->hasRole('teacher')) {
            return $report->submitted_by === $user->id;
        }

        return false;
    }

    public function view(?User $user, Report $report): bool
    {
        return $this->download($user, $report);
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->hasAnyRole(['admin', 'principal', 'registrar']);
    }

    public function approve(User $user, Report $report): bool
    {
        return $user->hasAnyRole(['admin', 'principal']);
    }

    public function reject(User $user, Report $report): bool
    {
        return $user->hasAnyRole(['admin', 'principal']);
    }
}