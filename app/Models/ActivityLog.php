<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'event_type','subject_type','subject_id','user_id',
        'description','meta','occurred_at'
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta'        => 'array',
    ];

    public static function record(
        string $event,
        ?Model $subject = null,
        ?string $description = null,
        array $meta = []
    ): self {
        return static::create([
            'event_type'   => $event,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'user_id'      => Auth::id(),
            'description'  => $description,
            'meta'         => $meta ?: null,
            'occurred_at'  => now(),
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}