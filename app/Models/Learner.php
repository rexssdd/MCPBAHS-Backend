<?php

namespace App\Models;

use App\Casts\PgBoolean;
use App\Enums\Learners\EnrollmentStatus;
use App\Enums\Learners\LearnerType;
use App\Enums\Sex;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property mixed $learner_type
 * @property mixed $enrollment_status
 * @property mixed $sex
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\Section|null $sectionAssignment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Learner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Learner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Learner query()
 * @mixin \Eloquent
 */
class Learner extends Model
{
    use HasUuids, SoftDeletes;

protected $keyType = 'string';

public $incrementing = false;
     protected $fillable = [

        // enrollment context
        'school_year',
        'grade_to_enroll',
        'learner_type',

        // remarks
        'remarks',

        // section assignment (admin-selected)
        'section_assignment_id',

        // LRN
        'has_lrn',
        'lrn',

        // personal info
        'last_name',
        'first_name',
        'middle_name',
        'name_extension',
        'birth_date',
        'sex',
        'age',
        'mother_tongue',
        'religion',
        'place_of_birth',

        // special groups
        'is_ip',
        'ip_specification',
        'is_4ps',
        'household_id_number',
        'is_pwd',
        'pwd_specification',

        // address
        'house_no_street',
        'street_name',
        'barangay',
        'municipality',
        'province',
        'country',
        'zip_code',

        // father
        'father_last_name',
        'father_first_name',
        'father_middle_name',
        'father_name_extension',

        // mother
        'mother_last_name',
        'mother_first_name',
        'mother_middle_name',
        'mother_name_extension',

        // contact
        'contact_number',

        // academic
        'last_grade_completed',
        'previous_school_name',
        'previous_school_address',
        'date_transferred',

        'shs_academic_track',
        'shs_strand',
        'academic_track',
        'academic_strand',

        // consents
        'image_usage_consent',
        'data_privacy_consent',
        'consented_at',

        // status
        'enrollment_status',

        // approval tracking
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',

    ];

    protected $casts = [

        'learner_type' => LearnerType::class,
        'enrollment_status' => EnrollmentStatus::class,
        'sex' => Sex::class,

        'has_lrn' => PgBoolean::class,
        'is_ip' => PgBoolean::class,
        'is_4ps' => PgBoolean::class,
        'is_pwd' => PgBoolean::class,
        'image_usage_consent' => PgBoolean::class,
        'data_privacy_consent' => PgBoolean::class,

        'birth_date' => 'date',
        'date_transferred' => 'date',
        'consented_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'deleted_at' => 'datetime',

        'age' => 'integer',
    ];

    public function sectionAssignment(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_assignment_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    // Add this method to the Learner model
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->withTrashed()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
