<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Inventory\Traits\InventoryStockMovementTrait;

/**
 * Class InventoryStockMovement.
 */
class InventoryStockMovement extends BaseModel
{
    use InventoryStockMovementTrait;

    protected $table = 'inventory_stock_movements';

    protected $fillable = [
        'stock_id',
        'created_by',
        'before',
        'after',
        'cost',
        'reason',
    ];

    /**
     * The belongsTo stock relationship.
     *
     * @return BelongsTo
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(config('inventory.models.inventory_stock'), 'stock_id', 'id');
    }
}
