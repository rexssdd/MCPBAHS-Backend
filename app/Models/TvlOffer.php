<?php

namespace App\Models;

use App\Casts\PgBoolean;
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
        'image_disk',
        'certifications',
        'display_order',
        'is_active',
    ];

    protected $hidden = [
        'id',
        'image_disk',
    ];

    protected $appends = [
        'image_url',
    ];

    protected $casts = [
        'certifications' => 'array',
        'is_active'      => PgBoolean::class,
    ];

    /**
     * Public URL for the offer's image, used both on the public homepage
     * TVL section and the admin TVL offers manager. Null when no image has
     * been uploaded so the frontend can fall back to the emoji icon.
     *
     * Uses the disk the image was actually stored on (image_disk) rather
     * than always assuming the currently configured default disk — older
     * rows created before image_disk existed fall back to that default
     * for backward compatibility.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        $disk = $this->image_disk ?: config('filesystems.default');

        $diskInstance = Storage::disk($disk);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $diskInstance */

        return $diskInstance->url($this->image_path);
    }
}