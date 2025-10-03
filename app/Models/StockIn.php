<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockIn extends Model
{
    use HasFactory;

    protected $table      = 'stock_in';
    protected $primaryKey = 'stockin_id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'stockin_id',
        'item_id',
        'supplier_id',
        'quantity',
        'price',
        'total_price',
        'stockin_date',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }
}