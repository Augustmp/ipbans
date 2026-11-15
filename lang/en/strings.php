<?php

return [
    'ip_ban' => 'IP Ban|IP Bans',
    'user' => 'User|Users',
    'ip' => 'IP address',
    'status' => 'Status',
    'active' => 'Banned',
    'lifted' => 'Unbanned',
    'all' => 'All',
    'reason' => 'Reason',
    'no_reason' => 'No reason given',
    'banned_by' => 'Banned by',
    'banned_at' => 'Banned',
    'unbanned_at' => 'Unbanned at',
    'unbanned_tooltip' => 'Unbanned :time by :user',
    'no_user' => 'No user',
    'user_hint' => 'Optional. Linked bans also show up on the user\'s edit page.',
    'ip_hint' => 'A single IP address or a CIDR range (e.g. 203.0.113.0/24)',
    'last_seen' => 'last seen :time',
    'known_ips' => 'Known IP addresses',
    'known_ips_hint' => 'All IPs this user has used are selected. Remove the ones you don\'t want to ban.',
    'no_known_ips' => 'No IP addresses recorded',
    'no_known_ips_hint' => 'No IP has been recorded for this user yet. Add one manually below.',
    'additional_ips' => 'Additional IP addresses',
    'empty' => 'No IP bans',
    'empty_user' => 'This user has no IP bans',

    'ban_ip' => 'Ban IP',
    'ban_user' => 'Ban user',
    'ban' => 'Ban permanently',
    'unban' => 'Unban',
    'unban_all' => 'Unban all',

    'modals' => [
        'ban_heading' => 'Ban user by IP',
        'ban_description' => 'The selected IP addresses are banned permanently and can no longer access the panel until you unban them.',
        'unban_heading' => 'Unban IP address',
        'unban_description' => 'Are you sure you want to unban :ip? It will be able to access the panel again immediately.',
        'unban_all_heading' => 'Unban all IP addresses',
        'unban_all_description' => 'This lifts every active IP ban linked to this user.',
    ],

    'notifications' => [
        'banned' => '{1} :count IP address banned|[2,*] :count IP addresses banned',
        'unbanned' => 'IP address unbanned',
        'unbanned_many' => '{0} Nothing to unban|{1} :count IP address unbanned|[2,*] :count IP addresses unbanned',
        'no_ips' => 'Select or enter at least one IP address',
    ],

    'validation' => [
        'invalid' => 'This is not a valid IP address or CIDR range.',
        'self_ban' => 'You cannot ban your own IP address.',
        'already_banned' => ':ip is already banned.',
    ],

    'blocked' => [
        'title' => 'Access denied',
        'message' => 'Your IP address has been banned from this panel. If you think this is a mistake, please contact an administrator.',
    ],

    'settings' => [
        'safety' => 'Safety',
        'whitelist' => 'Whitelisted IPs',
        'whitelist_placeholder' => 'New IP or CIDR range',
        'whitelist_hint' => 'These IP addresses can never be blocked, even if they match a ban. Add your own IP here to be safe.',
        'saved' => 'Settings saved',
    ],
];
