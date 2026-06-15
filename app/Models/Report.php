<?php

namespace App\Models;

use App\Enums\Reports\ReportStatus;
use App\Enums\Reports\ReportType;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'form_type',
        'school_year',
        'remarks',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'status',
        'archived_at',
        'archived_by',
    ];

    protected $hidden = [
        'id',
        'submitted_by',
        'reviewed_by',
        'archived_by',
    ];

    protected $casts = [
        'status'      => ReportStatus::class,
        'form_type'   => ReportType::class,
        'file_size'   => 'integer',
        'reviewed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | ROUTE MODEL BINDING FIX (CRITICAL)
    |--------------------------------------------------------------------------
    */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /* ── Relationships ── */

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /* ── Scopes ── */

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /* ── Helpers ── */

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}