<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Security headers to add to all responses
     */
    protected array $headers = [
        // Prevent clickjacking
        'X-Frame-Options' => 'SAMEORIGIN',
        
        // Prevent MIME type sniffing
        'X-Content-Type-Options' => 'nosniff',
        
        // XSS Protection (legacy browsers)
        'X-XSS-Protection' => '1; mode=block',
        
        // Referrer Policy
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        
        // Permissions Policy (formerly Feature-Policy)
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        
        // Prevent IE from running downloaded files
        'X-Download-Options' => 'noopen',
        
        // DNS Prefetch Control
        'X-DNS-Prefetch-Control' => 'off',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach ($this->headers as $header => $value) {
            $response->headers->set($header, $value);
        }

        // Add Content Security Policy for HTML responses
        if ($this->isHtmlResponse($response)) {
            $response->headers->set(
                'Content-Security-Policy',
                $this->buildContentSecurityPolicy()
            );
        }

        // Add Strict Transport Security (HSTS) in production
        if (config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }

    /**
     * Check if response is HTML
     */
    protected function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html') || empty($contentType);
    }

    /**
     * Build Content Security Policy header
     */
    protected function buildContentSecurityPolicy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
