<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CustomAttributeValue.
 */
class CustomAttributeValue extends BaseModel
{
    protected $table = 'custom_attribute_values';

    public $timestamps = false;

    protected $fillable = [
        'inventory_id',
        'custom_attribute_id',
        'string_val',
        'num_val',
        'date_val',
    ];

    /**
     * The hasOne inventories relationship.
     *
     * @return BelongsTo
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(config('inventory.models.inventory'), 'inventory_id');
    }

    /**
     * The hasOne attribute relationship.
     * 
     * @return BelongsTo
     */
    public function customAttribute(): BelongsTo
    {
        return $this->belongsTo(CustomAttribute::class);
    }
}
