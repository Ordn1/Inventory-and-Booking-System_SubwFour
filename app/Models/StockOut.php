<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOut extends Model
{
    protected $table = 'stock_out';
    protected $primaryKey = 'stockout_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'stockout_id',
        'item_id',
        'user_id',
        'quantity',
        'stockout_date',
        'reference_type',
        'reference_id',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}