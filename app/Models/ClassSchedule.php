<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Section|null $section
 * @property-read \App\Models\Personnel|null $teacher
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSchedule query()
 * @mixin \Eloquent
 */
class ClassSchedule extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'room_no',
        'subject',
        'school_year',
        'semester',
        'days',
        'start_time',
        'end_time',
        'section_id',
        'teacher_id'
    ];

    protected $hidden = [
        'id',
        'section_id',
        'teacher_id'
    ];

    public function casts(): array
    {
        return [
            'days' => 'array',
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'teacher_id');
    }
}
