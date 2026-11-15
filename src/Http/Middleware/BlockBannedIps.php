<?php

namespace Custom\IpBans\Http\Middleware;

use Closure;
use Custom\IpBans\Services\IpBanService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BlockBannedIps
{
    public function __construct(private readonly IpBanService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Wings (the node daemon) talks to the panel via the remote API, never block it.
        if ($ip && !$request->is('api/remote/*') && $this->isBlocked($ip)) {
            return $this->denied($request);
        }

        return $next($request);
    }

    private function isBlocked(string $ip): bool
    {
        try {
            return $this->service->shouldBlock($ip);
        } catch (Throwable) {
            // Fail open: a broken ban list must never take the whole panel down.
            return false;
        }
    }

    private function denied(Request $request): Response
    {
        $title = trans('ip-bans::strings.blocked.title');
        $message = trans('ip-bans::strings.blocked.message');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'errors' => [[
                    'code' => 'IpBanned',
                    'status' => '403',
                    'detail' => $message,
                ]],
            ], 403);
        }

        $title = e($title);
        $message = e($message);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{$title}</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f1115;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;padding:1.5rem}
        .card{max-width:28rem;text-align:center;background:#171a21;border:1px solid #262b36;border-radius:1rem;padding:2.5rem 2rem}
        .icon{width:4rem;height:4rem;margin:0 auto 1.25rem;border-radius:50%;background:rgba(239,68,68,.12);color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:2rem}
        h1{margin:0 0 .75rem;font-size:1.5rem}
        p{margin:0;color:#9ca3af;line-height:1.6}
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">&#9940;</div>
        <h1>{$title}</h1>
        <p>{$message}</p>
    </div>
</body>
</html>
HTML;

        return response($html, 403);
    }
}
