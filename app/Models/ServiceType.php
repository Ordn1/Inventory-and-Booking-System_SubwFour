<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceType extends Model
{
    use HasFactory;

    protected $table = 'service_types';

    protected $fillable = [
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Optional: relation to Service model.
     * Adjust foreign key / local key depending on how you store the type on services.
     *
     * Example if services table stores service_type_id:
     * return $this->hasMany(Service::class, 'service_type_id', 'id');
     *
     * Example if services table stores service_type (name string):
     * return $this->hasMany(Service::class, 'service_type', 'name');
     */
    public function services()
    {
        return $this->hasMany(\App\Models\Service::class); // adapt keys if needed
    }
}