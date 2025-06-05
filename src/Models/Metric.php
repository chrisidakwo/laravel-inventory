<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Metric.
 */
class Metric extends BaseModel
{
    protected $fillable = [
        "name",
        "symbol",
        "created_by",
    ];

    protected $table = 'metrics';

    /**
     * The hasMany inventory items relationship.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(config('inventory.models.inventory'), 'metric_id', 'id');
    }
}
