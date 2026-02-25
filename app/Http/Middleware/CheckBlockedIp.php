<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Models\SystemLog;

class CheckBlockedIp
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        
        if ($this->isBlocked($ip)) {
            SystemLog::security("Blocked IP attempted access", 'blocked_ip_access', [
                'ip_address' => $ip,
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied.',
                ], 403);
            }

            abort(403, 'Access denied. Your IP address has been blocked.');
        }

        return $next($request);
    }

    /**
     * Check if the IP address is blocked
     */
    protected function isBlocked(string $ip): bool
    {
        $blocklist = cache()->get('security_ip_blocklist', []);
        
        if (!isset($blocklist[$ip])) {
            return false;
        }

        $block = $blocklist[$ip];
        $expiresAt = Carbon::parse($block['expires_at']);
        
        // If block has expired, remove it from the list
        if ($expiresAt->isPast()) {
            unset($blocklist[$ip]);
            cache()->put('security_ip_blocklist', $blocklist, now()->addDays(30));
            return false;
        }

        return true;
    }
}
