<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class CustomAttribute.
 */
class CustomAttribute extends BaseModel
{
    protected $table = 'custom_attributes';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'display_name',
        'value_type',
        'reserved',
        'required',
        'rule',
        'rule_desc',
        'display_type',
        'has_default',
        'default_value',
    ];

    /**
	 * The BelongsToMany customAttributes relationship.
	 *
	 * @return BelongsToMany
	 */
    public function inventories(): BelongsToMany
    {
        return $this->belongsToMany(config('inventory.models.inventory'), 'custom_attribute_values', 'custom_attribute_id', 'inventory_id')
            ->withPivot("string_val", "num_val", "date_val")
            ->as("values")
            // ->using(CustomAttributeValue::class)
            ->withTimestamps();
    }

    /**
     * The belongsToMany customAttributeValues relationship.
     * 
     * @return HasMany
     */
    public function customAttributeValues(): HasMany
    {
        return $this->hasMany(CustomAttributeValue::class, 'custom_attribute_id');
    }
}
