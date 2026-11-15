<?php

namespace Custom\IpBans\Listeners;

use App\Models\User;
use Custom\IpBans\Models\UserIp;
use Custom\IpBans\Services\IpBanService;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RecordUserIp
{
    public function handle(Authenticated $event): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $user = $event->user;
        $ip = IpBanService::normalize((string) request()->ip());

        if (!$user instanceof User || $ip === null) {
            return;
        }

        // Only touch the database once per user/IP every 30 minutes.
        if (!Cache::add('ip-bans.seen.' . $user->id . '.' . md5($ip), true, now()->addMinutes(30))) {
            return;
        }

        try {
            $row = UserIp::query()->firstOrNew(['user_id' => $user->id, 'ip' => $ip]);

            if (!$row->exists) {
                $row->first_seen_at = now();
            }

            $row->last_seen_at = now();
            $row->save();
        } catch (Throwable) {
            // tracking must never break a request
        }
    }
}
