<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityIncident extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'ip_address',
        'user_agent',
        'target_resource',
        'description',
        'metadata',
        'status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'detected_at',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Incident types
     */
    const TYPE_BRUTE_FORCE       = 'brute_force';
    const TYPE_SUSPICIOUS_INPUT  = 'suspicious_input';
    const TYPE_RATE_LIMIT        = 'rate_limit';
    const TYPE_UNAUTHORIZED      = 'unauthorized_access';
    const TYPE_SQL_INJECTION     = 'sql_injection';
    const TYPE_XSS_ATTEMPT       = 'xss_attempt';
    const TYPE_SESSION_HIJACK    = 'session_hijack';
    const TYPE_ACCOUNT_LOCKOUT   = 'account_lockout';

    /**
     * Severity levels
     */
    const SEVERITY_LOW      = 'low';
    const SEVERITY_MEDIUM   = 'medium';
    const SEVERITY_HIGH     = 'high';
    const SEVERITY_CRITICAL = 'critical';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Record a security incident
     */
    public static function record(
        string $type,
        string $description,
        string $severity = self::SEVERITY_MEDIUM,
        ?int $userId = null,
        ?string $targetResource = null,
        array $metadata = []
    ): self {
        return static::create([
            'user_id'         => $userId,
            'type'            => $type,
            'severity'        => $severity,
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
            'target_resource' => $targetResource ?? request()->path(),
            'description'     => $description,
            'metadata'        => $metadata,
            'detected_at'     => now(),
        ]);
    }

    /**
     * Record a brute force attempt
     */
    public static function recordBruteForce(string $username, int $attempts): self
    {
        return static::record(
            self::TYPE_BRUTE_FORCE,
            "Multiple failed login attempts detected for username: {$username}",
            $attempts >= 10 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
            null,
            'login',
            ['username' => $username, 'attempts' => $attempts]
        );
    }

    /**
     * Record a suspicious input attempt
     */
    public static function recordSuspiciousInput(string $input, string $pattern): self
    {
        return static::record(
            self::TYPE_SUSPICIOUS_INPUT,
            "Suspicious input pattern detected matching: {$pattern}",
            self::SEVERITY_MEDIUM,
            auth()->id(),
            null,
            ['input_sample' => substr($input, 0, 200), 'pattern' => $pattern]
        );
    }

    /**
     * Record an unauthorized access attempt
     */
    public static function recordUnauthorized(string $resource, ?int $userId = null): self
    {
        return static::record(
            self::TYPE_UNAUTHORIZED,
            "Unauthorized access attempt to: {$resource}",
            self::SEVERITY_HIGH,
            $userId ?? auth()->id(),
            $resource
        );
    }

    /**
     * Record account lockout
     */
    public static function recordLockout(string $username, string $ipAddress): self
    {
        return static::record(
            self::TYPE_ACCOUNT_LOCKOUT,
            "Account locked due to multiple failed login attempts: {$username}",
            self::SEVERITY_HIGH,
            null,
            'login',
            ['username' => $username, 'locked_ip' => $ipAddress]
        );
    }

    /**
     * Scope for open incidents
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope for high severity
     */
    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Scope for recent incidents
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('detected_at', '>=', now()->subHours($hours));
    }

    /**
     * Resolve the incident
     */
    public function resolve(int $resolvedBy, ?string $notes = null): bool
    {
        return $this->update([
            'status'           => 'resolved',
            'resolved_by'      => $resolvedBy,
            'resolution_notes' => $notes,
            'resolved_at'      => now(),
        ]);
    }

    /**
     * Dismiss the incident
     */
    public function dismiss(int $dismissedBy, ?string $reason = null): bool
    {
        return $this->update([
            'status'           => 'dismissed',
            'resolved_by'      => $dismissedBy,
            'resolution_notes' => $reason ?? 'Dismissed as false positive',
            'resolved_at'      => now(),
        ]);
    }
}
