<?php

namespace Custom\IpBans\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property User $user
 * @property string $ip
 * @property ?Carbon $first_seen_at
 * @property ?Carbon $last_seen_at
 */
class UserIp extends Model
{
    protected $table = 'ip_ban_user_ips';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
