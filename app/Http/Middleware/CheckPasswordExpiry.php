<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordExpiry
{
    /**
     * Routes that are exempt from password expiry check
     */
    protected array $except = [
        'password.change',
        'password.update',
        'logout',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Check if current route is exempt
        $currentRoute = $request->route()?->getName();
        if ($currentRoute && in_array($currentRoute, $this->except)) {
            return $next($request);
        }

        // Check if password must be changed
        if ($user->mustChangePassword()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Password change required.',
                    'password_expired' => true,
                ], 403);
            }

            return redirect()->route('password.change')
                ->with('warning', 'Your password has expired. Please set a new password to continue.');
        }

        // Add password expiry warning to session if expiring soon
        $daysRemaining = $user->daysUntilPasswordExpires();
        $warningDays = config('security.password.warning_days', 14);
        
        if ($daysRemaining !== null && $daysRemaining <= $warningDays && $daysRemaining > 0) {
            session()->flash('password_warning', "Your password will expire in {$daysRemaining} days.");
        }

        return $next($request);
    }
}
