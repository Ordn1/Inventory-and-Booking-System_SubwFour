<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PasswordResetOtp extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'otp_code',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Generate a 6-digit OTP code
     */
    public static function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new OTP for a user
     */
    public static function createForUser(User $user): array
    {
        // Invalidate any existing unused OTPs for this user
        self::where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        $plainOtp = self::generateOtp();

        $otp = self::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_code' => Hash::make($plainOtp),
            'expires_at' => Carbon::now()->addMinutes(10), // OTP valid for 10 minutes
        ]);

        return [
            'otp' => $otp,
            'plain_code' => $plainOtp,
        ];
    }

    /**
     * Verify an OTP code
     */
    public static function verifyOtp(string $email, string $code): ?self
    {
        $otp = self::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otp) {
            return null;
        }

        if (!Hash::check($code, $otp->otp_code)) {
            return null;
        }

        return $otp;
    }

    /**
     * Mark OTP as used
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => Carbon::now()]);
    }

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if OTP is used
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Get the user that owns the OTP
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
