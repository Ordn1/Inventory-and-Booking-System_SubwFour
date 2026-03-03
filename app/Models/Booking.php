<?php

namespace App\Models;

use App\Casts\Encrypted;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'booking_id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'booking_id',
        'customer_name',
        'email',
        'contact_number',
        'service_type',
        'preferred_date',
        'preferred_time',
        'notes',
        'status',
    ];

    /**
     * Encrypted sensitive fields (customer PII)
     */
    protected $casts = [
        'email'          => Encrypted::class,
        'contact_number' => Encrypted::class,
        'preferred_date' => 'date',
    ];

    /**
     * Get masked email for display
     */
    public function getMaskedEmailAttribute(): string
    {
        $email = $this->email;
        if (!$email || !is_string($email)) return '—';
        
        $parts = explode('@', $email);
        if (count($parts) !== 2) return '***@***';
        
        $user = $parts[0];
        $domain = $parts[1];
        
        if (strlen($user) <= 2) {
            return $user[0] . '***@' . $domain;
        }
        return $user[0] . str_repeat('*', strlen($user) - 2) . substr($user, -1) . '@' . $domain;
    }

    /**
     * Get masked contact for display
     */
    public function getMaskedContactAttribute(): string
    {
        $contact = $this->contact_number;
        if (!$contact || !is_string($contact)) return '—';
        
        $digits = preg_replace('/[^0-9]/', '', $contact);
        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    protected static function booted()
    {
        static::creating(function ($m) {
            if (!$m->booking_id) {
                $last = static::orderBy('booking_id','desc')->first();
                $n = $last ? (int) preg_replace('/\D/','', $last->booking_id) : 0;
                $m->booking_id = 'BKG' . str_pad($n + 1, 4, '0', STR_PAD_LEFT);
            }
            if (!$m->status) {
                $m->status = 'pending';
            }
        });
    }
    public function service()
    {
        return $this->hasOne(Service::class, 'booking_id', 'booking_id');
    }
}