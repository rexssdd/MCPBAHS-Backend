<?php

namespace App\Models;

use App\Casts\PgBoolean;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $otp
 * @property \Illuminate\Support\Carbon $expires_at
 * @property bool $used
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp whereOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp whereUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPasswordOtp whereUserId($value)
 * @mixin \Eloquent
 */
class UserPasswordOtp extends Model
{
    protected $fillable = [
        'user_id',
        'otp',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => PgBoolean::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
