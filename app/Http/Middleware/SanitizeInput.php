<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should not be sanitized (e.g., passwords, rich text)
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        
        $sanitized = $this->sanitizeArray($input);
        
        $request->merge($sanitized);
        
        return $next($request);
    }

    /**
     * Recursively sanitize an array of inputs
     */
    protected function sanitizeArray(array $data): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            // Skip excluded fields
            if (in_array($key, $this->except, true)) {
                $result[$key] = $value;
                continue;
            }
            
            if (is_array($value)) {
                $result[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $result[$key] = $this->sanitizeString($value);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Sanitize a single string value
     */
    protected function sanitizeString(string $value): string
    {
        // Trim whitespace
        $value = trim($value);
        
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Strip tags but preserve content (prevent XSS while keeping text)
        $value = strip_tags($value);
        
        // Convert special HTML characters to entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        
        // Remove potential SQL injection patterns (additional layer - Laravel already handles this)
        $value = $this->removeSqlPatterns($value);
        
        return $value;
    }

    /**
     * Remove common SQL injection patterns
     * Note: This is an additional security layer; Laravel's query builder already prevents SQL injection
     */
    protected function removeSqlPatterns(string $value): string
    {
        // Remove common SQL injection patterns
        $patterns = [
            '/\bunion\s+select\b/i',
            '/\bselect\s+\*\s+from\b/i',
            '/\bdrop\s+table\b/i',
            '/\binsert\s+into\b/i',
            '/\bdelete\s+from\b/i',
            '/\bupdate\s+.*\s+set\b/i',
            '/--/',
            '/\/\*.*\*\//',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                // Log suspicious input for security monitoring
                \Log::warning('Potential SQL injection attempt detected', [
                    'input' => substr($value, 0, 200),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
                
                // Remove the suspicious pattern
                $value = preg_replace($pattern, '', $value);
            }
        }
        
        return $value;
    }
}
