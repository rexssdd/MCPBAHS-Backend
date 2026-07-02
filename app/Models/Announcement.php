<?php

namespace App\Models;

use App\Enums\Announcements\AnnouncementCategory;
use App\Enums\Announcements\AnnouncementStatus;
use App\Enums\Announcements\AnnouncementUrgency;
use App\Enums\Announcements\TargetAudience;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Announcement extends Model
{
    use HasPublicUuid, SoftDeletes;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'title',
        'message',

        'category',
        'status',
        'urgency',

        /**
         * Always stored as JSON array.
         *
         * Example:
         * ['sms']
         * ['sms', 'email']
         */
        'dissemination_modes',

        'target_audience',

        /**
         * Scheduled publish datetime.
         */
        'scheduled_at',

        /**
         * Actual posted datetime.
         */
        'posted_at',

        // Must be fillable so AnnouncementService::create() can stamp the
        // authenticated user via mass-assignment. Without this, Eloquent silently
        // discards the value and the non-nullable FK constraint throws an
        // integrity error on every POST /announcements.
        'created_by',
    ];

    /**
     * Hidden internal fields.
     */
    protected $hidden = [
        'id',
        'created_by',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'status'         => AnnouncementStatus::class,
        'urgency'        => AnnouncementUrgency::class,
        'category'       => AnnouncementCategory::class,
        'target_audience' => TargetAudience::class,

        /**
         * JSON array cast.
         */
        'dissemination_modes' => 'array',

        'scheduled_at' => 'datetime',
        'posted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Creator relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine if announcement is scheduled.
     */
    public function isScheduled(): bool
    {
        return !is_null($this->scheduled_at)
            && is_null($this->posted_at);
    }

    /**
     * Determine if announcement is already posted.
     */
    public function isPosted(): bool
    {
        return !is_null($this->posted_at);
    }

    /**
     * Determine if announcement should be dispatched now.
     */
    public function shouldDispatchNow(): bool
    {
        return is_null($this->scheduled_at)
            || $this->scheduled_at->isPast();
    }
}