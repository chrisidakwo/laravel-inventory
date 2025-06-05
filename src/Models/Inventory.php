<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stevebauman\Inventory\Traits\AssemblyTrait;
use Stevebauman\Inventory\Traits\CustomAttributeTrait;
use Stevebauman\Inventory\Traits\BundleTrait;
use Stevebauman\Inventory\Traits\InventoryVariantTrait;
use Stevebauman\Inventory\Traits\InventoryTrait;

/**
 * Class Inventory.
 */
class Inventory extends BaseModel
{
    use AssemblyTrait;
    use CustomAttributeTrait;
    use BundleTrait;
    use InventoryTrait;
    use InventoryVariantTrait;

    protected $table = 'inventories';

    protected $fillable = [
        'created_by',
        'category_id',
        'metric_id',
        'name',
        'sku',
        'description',
        'is_parent',
        'is_bundle',
    ];

    /**
     * The hasOne category relationship.
     *
     * @return HasOne
     */
    public function category(): HasOne
    {
        return $this->hasOne(config('inventory.models.category'), 'id', 'category_id');
    }

    /**
     * The hasOne metric relationship.
     *
     * @return HasOne
     */
    public function metric(): HasOne
    {
        return $this->hasOne(config('inventory.models.metric'), 'id', 'metric_id');
    }

    /**
     * The hasMany stocks relationship.
     *
     * @return HasMany
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(config('inventory.models.inventory_stock'), 'inventory_id', 'id');
    }

    /**
     * The belongsToMany suppliers relationship.
     *
     * @return BelongsToMany
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(config('inventory.models.supplier'), 'inventory_suppliers', 'inventory_id')
            ->withTimestamps();
    }

    /**
     * The belongsToMany supplier SKU relationship.
     * 
     * @return HasMany
     */
    public function supplierSKUs(): HasMany
    {
        return $this->hasMany(config('inventory.models.supplier'), 'inventory_suppliers', 'inventory_id');
    }

    /**
     * The belongsToMany assemblies relationship.
     *
     * @return BelongsToMany
     */
    public function assemblies(): BelongsToMany
    {
        return $this->belongsToMany($this, 'inventory_assemblies', 'inventory_id', 'part_id')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }

    /**
     * The belongsToMany bundles relationship.
     *
     * @return BelongsToMany
     */
    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany($this, 'inventory_bundles', 'inventory_id', 'component_id')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }

    /**
     * The BelongsToMany customAttributes relationship.
     *
     * @return BelongsToMany
     */
    public function customAttributes(): BelongsToMany
    {
        return $this->belongsToMany(CustomAttribute::class, 'custom_attribute_values', 'inventory_id', 'custom_attribute_id')
            ->withPivot("string_val", "num_val", "date_val")
            ->as("values")
            // ->using(CustomAttributeValues::class)
            ->withTimestamps();
    }

    /**
     * The belongsToMany attributeValues relationship.
     * 
     * @return HasMany
     */
     public function customAttributeValues(): HasMany
     {
        return $this->hasMany(CustomAttributeValue::class, 'inventory_id');
     }
}
