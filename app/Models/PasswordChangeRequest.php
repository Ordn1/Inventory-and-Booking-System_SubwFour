<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'reason',
        'status',
        'reviewed_by',
        'admin_comments',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the user associated with this request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee associated with this request
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the admin who reviewed this request
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if request is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Approve the request
     */
    public function approve(int $adminId, ?string $comments = null): bool
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $adminId,
            'admin_comments' => $comments,
            'reviewed_at' => now(),
        ]);

        // Set the user's must_change_password flag
        $this->user->update(['must_change_password' => true]);

        return true;
    }

    /**
     * Reject the request
     */
    public function reject(int $adminId, ?string $comments = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $adminId,
            'admin_comments' => $comments,
            'reviewed_at' => now(),
        ]);
    }
}
