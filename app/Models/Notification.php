<?php

namespace App\Models;

use App\Casts\PgBoolean;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'report_uuid',
        'is_read',
    ];

    protected $casts = [
        'is_read' => PgBoolean::class,
    ];

    protected $attributes = [
        'is_read' => false,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}