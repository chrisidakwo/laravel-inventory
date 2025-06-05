<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Inventory\Traits\InventoryTransactionHistoryTrait;

/**
 * Class InventoryTransactionPeriod.
 */
class InventoryTransactionHistory extends BaseModel
{
    use InventoryTransactionHistoryTrait;

    protected $table = 'inventory_transaction_histories';

    protected $fillable = [
        'created_by',
        'transaction_id',
        'state_before',
        'state_after',
        'quantity_before',
        'quantity_after',
    ];

    /**
     * The belongsTo transaction relationship.
     *
     * @return BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(config('inventory.models.inventory_transaction'), 'transaction_id', 'id');
    }
}
