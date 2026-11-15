<?php

return [
    'ip_ban' => 'IP-Sperre|IP-Sperren',
    'user' => 'Benutzer|Benutzer',
    'ip' => 'IP-Adresse',
    'status' => 'Status',
    'active' => 'Gesperrt',
    'lifted' => 'Entsperrt',
    'all' => 'Alle',
    'reason' => 'Grund',
    'no_reason' => 'Kein Grund angegeben',
    'banned_by' => 'Gesperrt von',
    'banned_at' => 'Gesperrt',
    'unbanned_at' => 'Entsperrt am',
    'unbanned_tooltip' => 'Entsperrt :time von :user',
    'no_user' => 'Kein Benutzer',
    'user_hint' => 'Optional. Verknüpfte Sperren werden auch auf der Bearbeiten-Seite des Benutzers angezeigt.',
    'ip_hint' => 'Eine einzelne IP-Adresse oder ein CIDR-Bereich (z. B. 203.0.113.0/24)',
    'last_seen' => 'zuletzt gesehen :time',
    'known_ips' => 'Bekannte IP-Adressen',
    'known_ips_hint' => 'Alle IPs, die dieser Benutzer verwendet hat, sind ausgewählt. Entferne die, die du nicht sperren willst.',
    'no_known_ips' => 'Keine IP-Adressen gespeichert',
    'no_known_ips_hint' => 'Für diesen Benutzer wurde noch keine IP gespeichert. Gib unten manuell eine ein.',
    'additional_ips' => 'Weitere IP-Adressen',
    'empty' => 'Keine IP-Sperren',
    'empty_user' => 'Dieser Benutzer hat keine IP-Sperren',

    'ban_ip' => 'IP sperren',
    'ban_user' => 'Benutzer sperren',
    'ban' => 'Dauerhaft sperren',
    'unban' => 'Entsperren',
    'unban_all' => 'Alle entsperren',

    'modals' => [
        'ban_heading' => 'Benutzer per IP sperren',
        'ban_description' => 'Die ausgewählten IP-Adressen werden dauerhaft gesperrt und haben keinen Zugriff mehr auf das Panel, bis du sie entsperrst.',
        'unban_heading' => 'IP-Adresse entsperren',
        'unban_description' => 'Bist du sicher, dass du :ip entsperren willst? Der Zugriff auf das Panel ist sofort wieder möglich.',
        'unban_all_heading' => 'Alle IP-Adressen entsperren',
        'unban_all_description' => 'Dadurch werden alle aktiven IP-Sperren dieses Benutzers aufgehoben.',
    ],

    'notifications' => [
        'banned' => '{1} :count IP-Adresse gesperrt|[2,*] :count IP-Adressen gesperrt',
        'unbanned' => 'IP-Adresse entsperrt',
        'unbanned_many' => '{0} Nichts zu entsperren|{1} :count IP-Adresse entsperrt|[2,*] :count IP-Adressen entsperrt',
        'no_ips' => 'Wähle mindestens eine IP-Adresse aus oder gib eine ein',
    ],

    'validation' => [
        'invalid' => 'Das ist keine gültige IP-Adresse oder kein gültiger CIDR-Bereich.',
        'self_ban' => 'Du kannst deine eigene IP-Adresse nicht sperren.',
        'already_banned' => ':ip ist bereits gesperrt.',
    ],

    'blocked' => [
        'title' => 'Zugriff verweigert',
        'message' => 'Deine IP-Adresse wurde für dieses Panel gesperrt. Wenn du denkst, dass dies ein Fehler ist, wende dich bitte an einen Administrator.',
    ],

    'settings' => [
        'safety' => 'Sicherheit',
        'whitelist' => 'Erlaubte IPs',
        'whitelist_placeholder' => 'Neue IP oder CIDR-Bereich',
        'whitelist_hint' => 'Diese IP-Adressen können nie blockiert werden, auch wenn sie von einer Sperre erfasst würden. Trage am besten deine eigene IP ein.',
        'saved' => 'Einstellungen gespeichert',
    ],
];
