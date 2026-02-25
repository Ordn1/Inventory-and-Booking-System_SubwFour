<?php

namespace App\Models;

use App\Casts\Encrypted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'address',
        'contact_number',
        'sss_number',
        'profile_picture',
    ];

    /**
     * Encrypted sensitive fields
     */
    protected $casts = [
        'sss_number'     => Encrypted::class,
        'contact_number' => Encrypted::class,
    ];

    /**
     * Get masked SSS number for display
     */
    public function getMaskedSssAttribute(): string
    {
        $sss = $this->sss_number;
        if (!$sss || !is_string($sss)) return '—';
        
        // Show only last 4 digits: ***-**-1234
        return '***-**-' . substr(preg_replace('/[^0-9]/', '', $sss), -4);
    }

    /**
     * Get masked contact for display
     */
    public function getMaskedContactAttribute(): string
    {
        $contact = $this->contact_number;
        if (!$contact || !is_string($contact)) return '—';
        
        // Show only last 4 digits
        $digits = preg_replace('/[^0-9]/', '', $contact);
        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}