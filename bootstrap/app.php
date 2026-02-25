<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware (applied to all requests)
        $middleware->prepend(\App\Http\Middleware\ForceHttps::class);
        $middleware->append(\App\Http\Middleware\CheckBlockedIp::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\SanitizeInput::class);
        
        // Route middleware aliases
        $middleware->alias([
            'role'            => \App\Http\Middleware\CheckRole::class,
            'rate.limit'      => \App\Http\Middleware\RateLimiter::class,
            'password.expiry' => \App\Http\Middleware\CheckPasswordExpiry::class,
            'security.policy' => \App\Http\Middleware\EnforceSecurityPolicies::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log all exceptions for security monitoring (except validation)
        $exceptions->report(function (Throwable $e) {
            // Skip validation exceptions from security logging
            if ($e instanceof ValidationException) {
                return;
            }
            
            // Log security-relevant exception details
            \Log::channel('security')->error('Exception occurred', [
                'type'       => get_class($e),
                'message'    => $e->getMessage(),
                'code'       => $e->getCode(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url'        => request()->fullUrl(),
                'user_id'    => auth()->id(),
            ]);
        });
        
        // Don't report certain exceptions to default log (reduce noise)
        $exceptions->dontReport([
            NotFoundHttpException::class,
        ]);
        
        // Render user-friendly error pages in production
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
            return response()->view('errors.404', [], 404);
        });
        
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        });
        
        $exceptions->render(function (HttpException $e, Request $request) {
            $status = $e->getStatusCode();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $status === 403 ? 'Access denied.' : 'An error occurred.',
                ], $status);
            }
            
            // Use custom error views if they exist
            if (view()->exists("errors.{$status}")) {
                return response()->view("errors.{$status}", [], $status);
            }
            
            return response()->view('errors.500', [], $status);
        });
    })->create();
