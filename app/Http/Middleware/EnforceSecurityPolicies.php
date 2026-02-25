<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemLog;

class EnforceSecurityPolicies
{
    /**
     * Handle an incoming request and enforce security policies.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Check if account is locked
        if ($this->isAccountLocked($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been locked due to security policy.']);
        }

        // Check session timeout
        if ($this->isSessionExpired($request)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            SystemLog::security('Session timeout', 'session_expired', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
            
            return redirect()->route('login')
                ->with('warning', 'Your session has expired due to inactivity.');
        }

        // Update last activity timestamp
        $request->session()->put('last_activity', time());

        // Log sensitive route access
        $this->logSensitiveAccess($request, $user);

        return $next($request);
    }

    /**
     * Check if the user account is locked
     */
    protected function isAccountLocked($user): bool
    {
        return $user->isLocked();
    }

    /**
     * Check if the session has expired due to inactivity
     */
    protected function isSessionExpired(Request $request): bool
    {
        $lastActivity = $request->session()->get('last_activity');
        
        if (!$lastActivity) {
            return false;
        }

        $timeoutMinutes = config('security.login.session_timeout_minutes', 120);
        $timeout = $timeoutMinutes * 60; // Convert to seconds
        
        return (time() - $lastActivity) > $timeout;
    }

    /**
     * Log access to sensitive routes
     */
    protected function logSensitiveAccess(Request $request, $user): void
    {
        $sensitiveRoutes = config('security.access.admin_only_routes', []);
        $currentRoute = $request->route()?->getName();
        
        if ($currentRoute && in_array($currentRoute, $sensitiveRoutes)) {
            SystemLog::audit('Sensitive route accessed', 'route_access', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => $currentRoute,
                'ip' => $request->ip(),
            ]);
        }
    }
}
