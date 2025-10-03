<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id','name','address','number','contact_person',
    ];

    protected $primaryKey = 'supplier_id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    public static $rules = [
        'name'           => 'required|string|max:255|unique:suppliers,name',
        'address'        => 'required|string|max:255|unique:suppliers,address',
        'number'         => 'required|string|max:15|unique:suppliers,number',
        'contact_person' => 'required|string|max:255|unique:suppliers,contact_person',
    ];

    protected static function booted()
    {
        static::creating(function ($supplier) {
            // Only generate if not provided (avoid double logic with controller)
            if (empty($supplier->supplier_id)) {
                $last = Supplier::orderBy('supplier_id','desc')->first();
                $n    = $last ? (int) substr($last->supplier_id,1) : 0;
                $supplier->supplier_id = 'S'.str_pad($n+1,3,'0',STR_PAD_LEFT);
            }
        });
    }
}