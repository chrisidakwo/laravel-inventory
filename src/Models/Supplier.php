<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stevebauman\Inventory\Traits\SupplierTrait;

class Supplier extends BaseModel
{
    use SupplierTrait;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'code',
        'address',
        'zip_code',
        'region',
        'city',
        'country',
        'contact_title',
        'contact_name',
        'contact_phone',
        'contact_fax',
        'contact_email',
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

    public function skus(): HasMany
    {
        return $this->hasMany(SupplierSKU::class, 'supplier_id', 'id');
    }
}
