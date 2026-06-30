<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TvlOffer extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'title',
        'description',
        'icon',
        'image_path',
        'certifications',
        'display_order',
        'is_active',
    ];

    protected $hidden = [
        'id',
    ];

    protected $appends = [
        'image_url',
    ];

    protected $casts = [
        'certifications' => 'array',
        'is_active'      => 'boolean',
    ];

    /**
     * Public URL for the offer's image, used both on the public homepage
     * TVL section and the admin TVL offers manager. Null when no image has
     * been uploaded so the frontend can fall back to the emoji icon.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        return Storage::disk(config('filesystems.default'))->url($this->image_path);
    }
}
