<?php

namespace Stevebauman\Inventory\Models;

/**
 * Class Metric.
 */
class Metric extends BaseModel
{
    protected $table = 'metrics';

    /**
     * The hasMany inventory items relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Inventory::class, 'metric_id', 'id');
    }
}
