<?php

namespace App\Models;

use App\Casts\PgBoolean;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'category',
        'is_published',
        'created_by',
    ];

    protected $hidden = [
        'id',
        'created_by',
    ];

    protected $casts = [
        'event_date'   => 'date:Y-m-d',
        'is_published' => PgBoolean::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
