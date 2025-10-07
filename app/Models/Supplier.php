<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

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
            if (empty($supplier->supplier_id)) {
                $last = Supplier::withTrashed()->orderBy('supplier_id','desc')->first();
                $n    = $last ? (int) preg_replace('/\D/','', $last->supplier_id) : 0;
                $supplier->supplier_id = 'S'.str_pad($n+1,3,'0',STR_PAD_LEFT);
            }
        });
    }
}