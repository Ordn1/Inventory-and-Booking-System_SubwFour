<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * Redirect HTTP requests to HTTPS in production environment.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enforce HTTPS in production
        if (config('app.env') === 'production') {
            if (!$request->secure()) {
                // Log the redirect for security monitoring
                \Log::info('HTTP to HTTPS redirect', [
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                ]);

                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        return $next($request);
    }
}
