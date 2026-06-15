<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\Reports\ReportStatus;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * FIX: Added `status_label` (for frontend badge display),
     * `is_for_admin_approval` / `is_for_principal_approval` flags
     * (so each role's UI can show the right action buttons),
     * and `original_filename` at the top level (frontend normalizeReport()
     * reads it from both raw.original_filename and raw.file.original_filename).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'form_type'  => $this->form_type,
            'school_year'=> $this->school_year,

            // Raw enum value — used for server-side transitions / API consumers.
            'status'     => $this->status,

            // Human-readable label consumed by the frontend status badges and filters.
            // Maps:  for_admin_approval    → "For Admin Approval"
            //        for_principal_approval → "For Principal Approval"
            //        approved               → "Approved"
            //        rejected               → "Disapproved"
            'status_label' => $this->status?->label(),

            // Convenience booleans so the frontend can hide/show action buttons
            // without re-parsing the raw enum string.
            'is_for_admin_approval'     => $this->status === ReportStatus::ForAdminApproval,
            'is_for_principal_approval' => $this->status === ReportStatus::ForPrincipalApproval,
            'is_approved'               => $this->status === ReportStatus::Approved,
            'is_rejected'               => $this->status === ReportStatus::Rejected,

            'remarks'    => $this->remarks,

            // Top-level filename so normalizeReport() on the frontend can find it
            // without having to dig into the nested file{} object.
            'original_filename' => $this->original_filename,

            'file' => [
                'original_filename' => $this->original_filename,
                'mime_type'         => $this->mime_type,
                'file_size'         => $this->file_size,
            ],

            'submitted_by' => $this->whenLoaded('submitter', fn () => [
                'uuid' => $this->submitter->uuid,
                'name' => $this->submitter->name,
            ]),
            'reviewed_by'  => $this->whenLoaded('reviewer', fn () => [
                'uuid' => $this->reviewer->uuid,
                'name' => $this->reviewer->name,
            ]),

            'reviewed_at' => $this->reviewed_at,
            'archived_at' => $this->archived_at,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}