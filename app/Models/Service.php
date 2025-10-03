<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'booking_id',
        'reference_code',
        'status',
        'labor_fee',
        'subtotal',
        'total',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'labor_fee'    => 'decimal:2',
        'subtotal'     => 'decimal:2',
        'total'        => 'decimal:2',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_PENDING     = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    protected static function booted(): void
    {
        static::creating(function (Service $service) {
            if (empty($service->reference_code)) {
                $next = (static::max('id') ?? 0) + 1;
                $service->reference_code = 'SRV-' . str_pad($next, 6, '0', STR_PAD_LEFT);
            }
            if (empty($service->status)) {
                $service->status = self::STATUS_PENDING;
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceItem::class);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED]);
    }

    public function recalcTotals(bool $save = false): void
    {
        $subtotal = $this->items->sum('line_total');
        $this->subtotal = $subtotal;
        $this->total = $subtotal + ($this->labor_fee ?? 0);
        if ($save) {
            $this->save();
        }
    }

    public function markStarted(): void
    {
        if (!$this->started_at) {
            $this->started_at = now();
            $this->save();
        }
    }

    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();
    }
}