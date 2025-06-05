<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stevebauman\Inventory\Traits\InventoryTransactionTrait;
use Stevebauman\Inventory\Interfaces\StateableInterface;

/**
 * Class InventoryTransaction.
 */
class InventoryTransaction extends BaseModel implements StateableInterface
{
    use InventoryTransactionTrait;

    protected $table = 'inventory_transactions';

    protected $fillable = [
        'created_by',
        'stock_id',
        'name',
        'state',
        'quantity',
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

    /**
     * The hasMany histories relationship.
     *
     * @return HasMany
     */
    public function histories(): HasMany
    {
        return $this->hasMany(config('inventory.models.inventory_transaction_history'), 'transaction_id', 'id');
    }
}
