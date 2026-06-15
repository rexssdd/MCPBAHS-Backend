<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read \App\Models\Personnel|null $adviser
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section query()
 * @mixin \Eloquent
 */
class Section extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'section_name',
        'grade_level',
        'school_year',

        'academic_track',
        'academic_strand',

        'adviser_id'
    ];

    protected $hidden = [
        'id',
        'adviser_id'
    ];

    public function learners(): HasMany
    {
        return $this->hasMany(Learner::class, 'section_assignment_id');
    }

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'section_id');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'adviser_id');
    }
}
