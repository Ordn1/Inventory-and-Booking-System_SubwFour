<?php

namespace App\Models;

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