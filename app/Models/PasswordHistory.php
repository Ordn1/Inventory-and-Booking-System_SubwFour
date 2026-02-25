<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class PasswordHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'password',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a password in history
     */
    public static function record(int $userId, string $hashedPassword): self
    {
        // Get the history count limit from config
        $historyCount = config('security.password.history_count', 5);

        // Create the new history entry
        $history = static::create([
            'user_id' => $userId,
            'password' => $hashedPassword,
            'created_at' => now(),
        ]);

        // Remove old entries beyond the limit
        $oldEntries = static::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->skip($historyCount)
            ->take(100)
            ->pluck('id');

        if ($oldEntries->isNotEmpty()) {
            static::whereIn('id', $oldEntries)->delete();
        }

        return $history;
    }

    /**
     * Check if a password was used before
     */
    public static function wasUsedBefore(int $userId, string $plainPassword): bool
    {
        $histories = static::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(config('security.password.history_count', 5))
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($plainPassword, $history->password)) {
                return true;
            }
        }

        return false;
    }
}
