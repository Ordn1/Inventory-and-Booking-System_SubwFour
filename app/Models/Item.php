<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'item_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'item_id',
        'itemctgry_id',
        'name',
        'description',
        'quantity',
        'unit_price',
        'unit',
        'active',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'active'     => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'itemctgry_id', 'itemctgry_id');
    }

    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class, 'item_id', 'item_id');
    }

    public function serviceItems(): HasMany
    {
        return $this->hasMany(ServiceItem::class, 'item_id', 'item_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $pk = $model->getKeyName();
            if (!empty($model->{$pk})) return;

            $last = self::withTrashed()->orderByDesc($pk)->first();
            $n = $last ? (int) preg_replace('/\D/','', $last->{$pk}) : 0;
            $model->{$pk} = 'ITM' . str_pad($n + 1, 4, '0', STR_PAD_LEFT);
        });
    }
}