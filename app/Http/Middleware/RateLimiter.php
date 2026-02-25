<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimiter
{
    /**
     * Default rate limit settings
     */
    protected int $maxAttempts = 60;  // Max requests
    protected int $decayMinutes = 1;  // Time window in minutes

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;

        $key = $this->resolveRequestSignature($request);

        if ($this->tooManyAttempts($key)) {
            return $this->buildRateLimitResponse($key);
        }

        $this->hit($key);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $key);
    }

    /**
     * Generate a unique key for the request
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();
        
        if ($user) {
            return 'rate_limit:user:' . $user->id;
        }
        
        return 'rate_limit:ip:' . $request->ip();
    }

    /**
     * Check if too many attempts have been made
     */
    protected function tooManyAttempts(string $key): bool
    {
        return Cache::get($key, 0) >= $this->maxAttempts;
    }

    /**
     * Record a request hit
     */
    protected function hit(string $key): void
    {
        $hits = Cache::get($key, 0);
        
        if ($hits === 0) {
            Cache::put($key, 1, now()->addMinutes($this->decayMinutes));
        } else {
            Cache::increment($key);
        }
    }

    /**
     * Get remaining attempts
     */
    protected function remainingAttempts(string $key): int
    {
        return max(0, $this->maxAttempts - Cache::get($key, 0));
    }

    /**
     * Get seconds until rate limit resets
     */
    protected function retryAfter(string $key): int
    {
        return $this->decayMinutes * 60;
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildRateLimitResponse(string $key): Response
    {
        $retryAfter = $this->retryAfter($key);
        
        // Log rate limit violation
        \Log::warning('Rate limit exceeded', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'path' => request()->path(),
        ]);
        
        return response()->json([
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $this->maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders(Response $response, string $key): Response
    {
        $response->headers->set('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->remainingAttempts($key));
        
        return $response;
    }
}
