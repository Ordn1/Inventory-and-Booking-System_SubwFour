<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use SoftDeletes; 

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'password_changed_at',
        'must_change_password',
        'failed_login_count',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'locked_until' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get password history for this user
     */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    /**
     * Check if user account is active
     */
    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    /**
     * Check if user account is locked
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Check if password has expired
     */
    public function isPasswordExpired(): bool
    {
        $expiryDays = (int) config('security.password.expiry_days', 90);
        
        if (!$this->password_changed_at) {
            return true; // Never changed, consider expired
        }

        return $this->password_changed_at->addDays($expiryDays)->isPast();
    }

    /**
     * Check if password needs to be changed
     */
    public function mustChangePassword(): bool
    {
        return $this->must_change_password || $this->isPasswordExpired();
    }

    /**
     * Update password with history tracking
     */
    public function updatePassword(string $newPassword): bool
    {
        // Check password history
        if (PasswordHistory::wasUsedBefore($this->id, $newPassword)) {
            return false;
        }

        // Hash and update password
        $hashedPassword = Hash::make($newPassword);
        
        $this->update([
            'password' => $hashedPassword,
            'password_changed_at' => now(),
            'must_change_password' => false,
        ]);

        // Record in password history
        PasswordHistory::record($this->id, $hashedPassword);

        return true;
    }

    /**
     * Increment failed login attempts
     */
    public function incrementFailedLogins(): void
    {
        $this->increment('failed_login_count');
        
        $maxAttempts = config('security.login.max_attempts', 5);
        $lockoutMinutes = config('security.login.lockout_minutes', 30);
        
        if ($this->failed_login_count >= $maxAttempts) {
            $this->update([
                'locked_until' => now()->addMinutes($lockoutMinutes),
            ]);
            
            SecurityIncident::recordLockout($this, 'Too many failed login attempts');
        }
    }

    /**
     * Reset failed login count on successful login
     */
    public function resetFailedLogins(): void
    {
        $this->update([
            'failed_login_count' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Get days until password expires
     */
    public function daysUntilPasswordExpires(): ?int
    {
        if (!$this->password_changed_at) {
            return 0;
        }

        $expiryDays = (int) config('security.password.expiry_days', 90);
        $expiryDate = $this->password_changed_at->addDays($expiryDays);
        
        if ($expiryDate->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($expiryDate);
    }
}