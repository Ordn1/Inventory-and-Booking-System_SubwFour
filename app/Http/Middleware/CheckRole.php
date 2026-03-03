<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userRole = Auth::user()->role;

        // Check if user has any of the allowed roles
        if (!in_array($userRole, $roles)) {
            // Redirect based on user's actual role
            if ($userRole === 'employee') {
                return redirect()->route('employee.dashboard')
                    ->with('error', 'You do not have permission to access that page.');
            }
            
            if ($userRole === 'security') {
                return redirect()->route('security.dashboard')
                    ->with('error', 'You do not have permission to access that page.');
            }
            
            return redirect()->route('system')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
