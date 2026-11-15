<?php

namespace Custom\IpBans\Models;

use App\Models\User;
use Carbon\Carbon;
use Custom\IpBans\Services\IpBanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $ip Single IP address or CIDR range
 * @property ?int $user_id
 * @property ?User $user
 * @property ?int $banned_by
 * @property ?User $bannedBy
 * @property ?string $reason
 * @property ?Carbon $unbanned_at
 * @property ?int $unbanned_by
 * @property ?User $unbannedBy
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|IpBan active()
 */
class IpBan extends Model
{
    protected $table = 'ip_bans';

    protected $fillable = [
        'ip',
        'user_id',
        'banned_by',
        'reason',
        'unbanned_at',
        'unbanned_by',
    ];

    protected function casts(): array
    {
        return [
            'unbanned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => app(IpBanService::class)->flushCache();

        static::saved($flush);
        static::deleted($flush);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unbanned_at');
    }

    public function isActive(): bool
    {
        return $this->unbanned_at === null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function unbannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unbanned_by');
    }
}
