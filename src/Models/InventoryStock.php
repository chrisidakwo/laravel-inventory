<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stevebauman\Inventory\Traits\InventoryStockTrait;

/**
 * Class InventoryStock.
 */
class InventoryStock extends BaseModel
{
    use InventoryStockTrait;

    protected $table = 'inventory_stocks';

    protected $fillable = [
        'inventory_id',
        'location_id',
        'quantity',
        'aisle',
        'row',
        'bin',
    ];

    /**
     * The belongsTo inventory item relationship.
     *
     * @return BelongsTo
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(config('inventory.models.inventory'), 'inventory_id', 'id');
    }

    /**
     * The hasMany movements relationship.
     *
     * @return HasMany
     */
    public function movements(): HasMany
    {
        dd(config('inventory.models.inventory_stock_movement'));
        return $this->hasMany(config('inventory.models.inventory_stock_movement'), 'stock_id', 'id');
    }

    /**
     * The hasMany transactions relationship.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(config('inventory.models.inventory_transaction'), 'stock_id', 'id');
    }

    /**
     * The hasOne location relationship.
     *
     * @return HasOne
     */
    public function location(): HasOne
    {
        return $this->hasOne(config('inventory.models.location'), 'id', 'location_id');
    }
}
