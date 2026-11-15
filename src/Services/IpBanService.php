<?php

namespace Custom\IpBans\Services;

use App\Models\User;
use Custom\IpBans\Models\IpBan;
use Custom\IpBans\Models\UserIp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

class IpBanService
{
    public const CACHE_KEY = 'ip-bans.active-targets';

    /** @return string[] */
    public function activeTargets(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => IpBan::query()->active()->pluck('ip')->unique()->values()->all());
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function isBanned(string $ip): bool
    {
        $targets = $this->activeTargets();

        return $targets !== [] && IpUtils::checkIp($ip, $targets);
    }

    /** @return string[] */
    public function whitelist(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) config('ip-bans.whitelist', '')))));
    }

    public function isWhitelisted(string $ip): bool
    {
        $whitelist = $this->whitelist();

        return $whitelist !== [] && IpUtils::checkIp($ip, $whitelist);
    }

    public function shouldBlock(string $ip): bool
    {
        return !$this->isWhitelisted($ip) && $this->isBanned($ip);
    }

    /** Returns a canonical IP / CIDR string or null if the input is not valid. */
    public static function normalize(string $target): ?string
    {
        $target = trim($target);

        if ($target === '') {
            return null;
        }

        if (str_contains($target, '/')) {
            [$ip, $prefix] = explode('/', $target, 2);

            if (!filter_var($ip, FILTER_VALIDATE_IP) || !ctype_digit($prefix)) {
                return null;
            }

            $isV6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
            $max = $isV6 ? 128 : 32;
            $prefix = (int) $prefix;

            // /0 would ban the whole internet
            if ($prefix < 1 || $prefix > $max) {
                return null;
            }

            $canonical = inet_ntop(inet_pton($ip));

            return $prefix === $max ? $canonical : "$canonical/$prefix";
        }

        if (!filter_var($target, FILTER_VALIDATE_IP)) {
            return null;
        }

        return inet_ntop(inet_pton($target));
    }

    /** Would banning this target also ban the IP the admin is currently using? */
    public function wouldLockOut(string $target, ?string $requestIp): bool
    {
        return $requestIp !== null && $requestIp !== '' && IpUtils::checkIp($requestIp, $target);
    }

    public function isActivelyBanned(string $target): bool
    {
        return IpBan::query()->active()->where('ip', $target)->exists();
    }

    public function ban(string $target, ?User $user = null, ?User $by = null, ?string $reason = null): IpBan
    {
        $normalized = self::normalize($target) ?? throw new InvalidArgumentException("Invalid IP address or range: $target");

        $existing = IpBan::query()->active()->where('ip', $normalized)->first();

        if ($existing) {
            return $existing;
        }

        return IpBan::create([
            'ip' => $normalized,
            'user_id' => $user?->id,
            'banned_by' => $by?->id,
            'reason' => filled($reason) ? $reason : null,
        ]);
    }

    /**
     * @param  iterable<string>  $targets
     * @return Collection<int, IpBan>
     */
    public function banUser(User $user, iterable $targets, ?User $by = null, ?string $reason = null): Collection
    {
        return DB::transaction(function () use ($user, $targets, $by, $reason) {
            $bans = collect();

            foreach ($targets as $target) {
                $bans->push($this->ban($target, $user, $by, $reason));
            }

            return $bans;
        });
    }

    public function unban(IpBan $ban, ?User $by = null): void
    {
        if (!$ban->isActive()) {
            return;
        }

        $ban->forceFill([
            'unbanned_at' => now(),
            'unbanned_by' => $by?->id,
        ])->save();
    }

    public function unbanAllForUser(User $user, ?User $by = null): int
    {
        $count = 0;

        IpBan::query()->active()->where('user_id', $user->id)->get()->each(function (IpBan $ban) use ($by, &$count) {
            $this->unban($ban, $by);
            $count++;
        });

        return $count;
    }

    /**
     * Every IP we know this user has used, newest first. Value is the last seen time (null if unknown).
     *
     * @return Collection<string, ?\Carbon\Carbon>
     */
    public function knownIps(User $user): Collection
    {
        $ips = UserIp::query()
            ->where('user_id', $user->id)
            ->get()
            ->mapWithKeys(fn (UserIp $row) => [$row->ip => $row->last_seen_at]);

        // Also pick up IPs from the panel's activity log, so history from before the plugin was installed counts.
        try {
            if (class_exists(\App\Models\ActivityLog::class)) {
                \App\Models\ActivityLog::query()
                    ->where('actor_id', $user->id)
                    ->where('actor_type', $user->getMorphClass())
                    ->whereNotNull('ip')
                    ->distinct()
                    ->pluck('ip')
                    ->each(function ($ip) use ($ips) {
                        $normalized = self::normalize((string) $ip);

                        if ($normalized !== null && !$ips->has($normalized)) {
                            $ips->put($normalized, null);
                        }
                    });
            }
        } catch (Throwable) {
            // activity log not available, tracked IPs are enough
        }

        return $ips->sortByDesc(fn ($lastSeen) => $lastSeen?->timestamp ?? 0);
    }
}
