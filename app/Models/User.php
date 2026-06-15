<?php

namespace App\Models;

use App\Enums\Users\AccountStatus;
use App\Exceptions\InvitationAlreadyAcceptedException;
use App\Traits\HasPublicUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $uuid
 * @property AccountStatus $account_status
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Announcement> $announcements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Learner> $approvedLearners
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Report> $reports
 * @property-read \App\Models\Personnel|null $personnel
 *
 * @mixin Builder
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasPublicUuid;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'account_status',
        'email_verified_at',
        'last_login_at',
        'invitation_sent_at',
        'invitation_accepted_at',
    ];

    protected $hidden = [
        'id',
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invitation_sent_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',

            'password' => 'hashed',
            'account_status' => AccountStatus::class,
        ];
    }

    public function personnel(): HasOne
    {
        return $this->hasOne(Personnel::class, 'user_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'submitted_by');
    }

    public function reviewedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'reviewed_by');
    }

    public function reviewedLearners(): HasMany
    {
        return $this->hasMany(Learner::class, 'reviewed_by');
    }

    public function approvedLearners(): HasMany
    {
        return $this->hasMany(Learner::class, 'approved_by');
    }

    public function isActive(): bool
    {
        return $this->account_status === AccountStatus::Active;
    }

    public function ensureNotActive(): void
    {
        if ($this->isActive()) {
            throw new InvitationAlreadyAcceptedException();
        }
    }

    public function submittedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'submitted_by');
    }
}