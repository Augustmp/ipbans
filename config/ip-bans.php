<?php

return [
    // Comma separated IPs / CIDR ranges that can never be blocked (safety net against admin lockouts).
    'whitelist' => env('IPBANS_WHITELIST', ''),
];
