<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemCategory extends Model
{
    use HasFactory;

    protected $primaryKey = 'itemctgry_id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'itemctgry_id',
        'name',
        'description',
        'active',
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'itemctgry_id', 'itemctgry_id');
    }
}