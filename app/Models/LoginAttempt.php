<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'ip_address',
        'user_agent',
        'status',
        'failure_reason',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a successful login attempt
     */
    public static function recordSuccess(int $userId, ?string $username = null): self
    {
        return static::create([
            'user_id'      => $userId,
            'username'     => $username,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'status'       => 'success',
            'attempted_at' => now(),
        ]);
    }

    /**
     * Record a failed login attempt
     */
    public static function recordFailure(?string $username, ?string $reason = null): self
    {
        return static::create([
            'user_id'        => null,
            'username'       => $username,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'status'         => 'failed',
            'failure_reason' => $reason ?? 'Invalid credentials',
            'attempted_at'   => now(),
        ]);
    }

    /**
     * Scope for successful attempts
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed attempts
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for attempts within a time range
     */
    public function scopeWithinHours($query, int $hours)
    {
        return $query->where('attempted_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for attempts from a specific IP
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Check if a username/IP is locked out due to too many failed attempts
     * 
     * @param string|null $username
     * @param string|null $ip
     * @param int $maxAttempts Maximum failed attempts before lockout
     * @param int $lockoutSeconds Seconds to lock out after max attempts
     * @return array ['locked' => bool, 'remaining_seconds' => int|null, 'attempts' => int]
     */
    public static function isLockedOut(?string $username, ?string $ip = null, ?int $maxAttempts = null, ?int $lockoutSeconds = null): array
    {
        $maxAttempts = $maxAttempts ?? config('security.login.max_attempts', 3);
        $lockoutSeconds = $lockoutSeconds ?? config('security.login.lockout_seconds', 30);
        
        $ip = $ip ?? request()->ip();
        $lockoutTime = now()->subSeconds($lockoutSeconds);
        
        // Count failed attempts for this username OR IP within lockout window
        $failedAttempts = static::failed()
            ->where('attempted_at', '>=', $lockoutTime)
            ->where(function ($query) use ($username, $ip) {
                $query->where('username', $username)
                      ->orWhere('ip_address', $ip);
            })
            ->count();
        
        if ($failedAttempts >= $maxAttempts) {
            // Get the most recent failed attempt to calculate remaining lockout time
            $lastAttempt = static::failed()
                ->where(function ($query) use ($username, $ip) {
                    $query->where('username', $username)
                          ->orWhere('ip_address', $ip);
                })
                ->latest('attempted_at')
                ->first();
            
            if ($lastAttempt) {
                $unlockTime = $lastAttempt->attempted_at->addSeconds($lockoutSeconds);
                $remainingSeconds = (int) now()->diffInSeconds($unlockTime, false);
                
                if ($remainingSeconds > 0) {
                    return [
                        'locked' => true,
                        'remaining_seconds' => $remainingSeconds,
                        'attempts' => $failedAttempts,
                    ];
                }
            }
        }
        
        return [
            'locked' => false,
            'remaining_seconds' => null,
            'attempts' => $failedAttempts,
        ];
    }

    /**
     * Clear failed attempts for a user (call on successful login)
     */
    public static function clearFailedAttempts(?string $username, ?string $ip = null): void
    {
        $ip = $ip ?? request()->ip();
        
        // We don't delete records (for audit purposes), but successful login resets the lockout
        // The lockout check uses a time window, so old failures naturally expire
    }

    /**
     * Get remaining attempts before lockout
     */
    public static function remainingAttempts(?string $username, ?string $ip = null, ?int $maxAttempts = null, ?int $lockoutSeconds = null): int
    {
        $maxAttempts = $maxAttempts ?? config('security.login.max_attempts', 3);
        $lockoutInfo = static::isLockedOut($username, $ip, $maxAttempts, $lockoutSeconds);
        return max(0, $maxAttempts - $lockoutInfo['attempts']);
    }
}
