<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SupplierSKU extends BaseModel
{
    protected $table = 'inventory_suppliers';

    protected $fillable = [
        'supplier_sku'
    ];

    /**
     * The belongsToMany items relationship.
     *
     * @return BelongsToMany
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(config('inventory.models.inventory'), 'inventory_suppliers', 'supplier_id')->withTimestamps();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(config('inventory.models.supplier'), 'inventory_suppliers', 'supplier_id');
    }
}
