# IP Bans

Permanently ban users by IP address. A ban stays active until an admin unbans it.

## Features

- Open a user in the admin panel (edit page) and see all of their IP bans right there
- **Ban user** button: pick from the IPs the user has used (or add your own, incl. CIDR ranges like `203.0.113.0/24`) and add a reason
- **Unban** a single IP or **Unban all** with one click, ban history is kept (who banned, who unbanned, when)
- Global **IP Bans** admin page to see/search/filter every ban and ban IPs manually
- Banned IPs get a 403 page for the panel, and a JSON error for API requests
- IPs are recorded when users use the panel, and history from the panel activity log is picked up too
- Safety nets: you can't ban your own IP, an IP whitelist (plugin settings), and Wings (`/api/remote/*`) is never blocked
- Fail-open: if the ban list ever can't be read, the panel keeps working
- English and German translations

## Notes

- This blocks access to the **panel** (web + API). It does not firewall game server ports.
- Behind a proxy / Cloudflare, make sure Pelican's trusted proxies are configured, otherwise every visitor looks like the proxy IP.
- Permissions: the plugin adds an `ipBan` permission set to roles (view / create / update / delete).
