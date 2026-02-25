<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'level',
        'action',
        'message',
        'context',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'logged_at',
    ];

    protected $casts = [
        'context'   => 'array',
        'logged_at' => 'datetime',
    ];

    /**
     * Log levels and their severity (higher = more severe)
     */
    const LEVELS = [
        'debug'     => 0,
        'info'      => 1,
        'notice'    => 2,
        'warning'   => 3,
        'error'     => 4,
        'critical'  => 5,
        'alert'     => 6,
        'emergency' => 7,
    ];

    /**
     * Log channels
     */
    const CHANNEL_SECURITY = 'security';
    const CHANNEL_AUDIT    = 'audit';
    const CHANNEL_ERROR    = 'error';
    const CHANNEL_INFO     = 'info';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a log entry
     */
    public static function log(
        string $channel,
        string $level,
        string $message,
        ?string $action = null,
        array $context = [],
        ?int $userId = null
    ): self {
        return static::create([
            'user_id'    => $userId ?? auth()->id(),
            'channel'    => $channel,
            'level'      => $level,
            'action'     => $action,
            'message'    => $message,
            'context'    => !empty($context) ? $context : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url'        => request()->fullUrl(),
            'method'     => request()->method(),
            'logged_at'  => now(),
        ]);
    }

    /**
     * Log a security event
     */
    public static function security(string $message, ?string $action = null, array $context = []): self
    {
        return static::log(self::CHANNEL_SECURITY, 'warning', $message, $action, $context);
    }

    /**
     * Log an audit event
     */
    public static function audit(string $message, ?string $action = null, array $context = []): self
    {
        return static::log(self::CHANNEL_AUDIT, 'info', $message, $action, $context);
    }

    /**
     * Log an error
     */
    public static function error(string $message, ?string $action = null, array $context = []): self
    {
        return static::log(self::CHANNEL_ERROR, 'error', $message, $action, $context);
    }

    /**
     * Log info
     */
    public static function info(string $message, ?string $action = null, array $context = []): self
    {
        return static::log(self::CHANNEL_INFO, 'info', $message, $action, $context);
    }

    /**
     * Scope for filtering by channel
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for filtering by level
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for filtering by minimum level severity
     */
    public function scopeMinLevel($query, string $level)
    {
        $minSeverity = self::LEVELS[$level] ?? 0;
        $levels = array_filter(self::LEVELS, fn($severity) => $severity >= $minSeverity);
        return $query->whereIn('level', array_keys($levels));
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('logged_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get level badge class for UI
     */
    public function getLevelBadgeClassAttribute(): string
    {
        return match($this->level) {
            'emergency', 'alert', 'critical' => 'badge-danger',
            'error'                          => 'badge-danger',
            'warning'                        => 'badge-warning',
            'notice', 'info'                 => 'badge-info',
            'debug'                          => 'badge-secondary',
            default                          => 'badge-info',
        };
    }

    /**
     * Get channel badge class for UI
     */
    public function getChannelBadgeClassAttribute(): string
    {
        return match($this->channel) {
            'security' => 'badge-danger',
            'audit'    => 'badge-info',
            'error'    => 'badge-danger',
            'info'     => 'badge-success',
            default    => 'badge-secondary',
        };
    }
}
