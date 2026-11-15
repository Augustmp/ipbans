<?php

namespace Custom\IpBans\Policies;

use App\Policies\DefaultAdminPolicies;

class IpBanPolicy
{
    use DefaultAdminPolicies;

    protected string $modelName = 'ipBan';
}
