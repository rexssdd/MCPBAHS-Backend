<?php

namespace App\Models;

use App\Enums\Personnel\EmploymentStatus;
use App\Enums\Personnel\PersonnelPosition;
use App\Enums\Sex;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property Sex $sex
 * @property PersonnelPosition $position
 * @property EmploymentStatus $employment_status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClassSchedule> $classSchedules
 * @property-read int|null $class_schedules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Section> $section
 * @property-read int|null $section_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Personnel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Personnel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Personnel onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Personnel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Personnel withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Personnel withoutTrashed()
 * @mixin \Eloquent
 */
class Personnel extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'personnel_id_number',

        'first_name',
        'middle_name',
        'last_name',

        'email',
        'phone_number',

        'date_of_birth',
        'sex',

        'country',
        'region',
        'province',
        'brgy_street_address',
        'city',
        'postal_code',

        'teaching_load',

        'position',
        'department',
        'employment_status',

        'photo_path',
    ];

    protected $appends = [
        'photo_url',
    ];

    protected $hidden = [
        'id',
        'user_id',
        'deleted_at'
    ];

    protected $casts = [
        'sex' => Sex::class,
        'position' => PersonnelPosition::class,
        'employment_status' => EmploymentStatus::class,

        'date_of_birth' => 'date',
        'teaching_load' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function section(): HasMany
    {
        return $this->hasMany(Section::class, 'adviser_id');
    }

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'teacher_id');
    }

    // attribute
    public function getFullNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])
        ->filter()
        ->implode(' ');
    }

    /**
     * Public, signed-URL-free link to the personnel's profile photo so the
     * public faculty directory can show real faces. Returns null when no
     * photo has been uploaded yet so the frontend can fall back to initials.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo_path)) {
            return null;
        }

        return Storage::disk(config('filesystems.default'))->url($this->photo_path);
    }
}
