<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSecurityReport extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'reviewed_by',
        'admin_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_RESOLVED = 'resolved';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    const CATEGORY_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    const CATEGORY_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    const CATEGORY_DATA_BREACH = 'data_breach';
    const CATEGORY_SYSTEM_ISSUE = 'system_issue';
    const CATEGORY_GENERAL = 'general';

    /**
     * Get the user who submitted the report
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee who submitted the report
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the admin/security who reviewed the report
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending reports
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Check if report is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Acknowledge the report
     */
    public function acknowledge(int $reviewerId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'reviewed_by' => $reviewerId,
            'admin_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Resolve the report
     */
    public function resolve(int $reviewerId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'reviewed_by' => $reviewerId,
            'admin_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }
}
