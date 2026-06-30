<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class TvlOffer extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'title',
        'description',
        'icon',
        'certifications',
        'display_order',
        'is_active',
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'certifications' => 'array',
        'is_active'      => 'boolean',
    ];
}
