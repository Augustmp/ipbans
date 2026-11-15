<?php

namespace Custom\IpBans\Rules;

use Closure;
use Custom\IpBans\Services\IpBanService;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a single IP address / CIDR range.
 * This is a rule object (not a closure) on purpose: Filament tries to evaluate closures
 * passed as rules with its own injection and can't resolve $attribute / $value / $fail.
 */
class IpTargetRule implements ValidationRule
{
    public function __construct(private readonly bool $checkBanState = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = IpBanService::normalize((string) $value);

        if ($normalized === null) {
            $fail(trans('ip-bans::strings.validation.invalid'));

            return;
        }

        if (!$this->checkBanState) {
            return;
        }

        $service = app(IpBanService::class);

        if ($service->wouldLockOut($normalized, request()->ip())) {
            $fail(trans('ip-bans::strings.validation.self_ban'));

            return;
        }

        if ($service->isActivelyBanned($normalized)) {
            $fail(trans('ip-bans::strings.validation.already_banned', ['ip' => $normalized]));
        }
    }
}
