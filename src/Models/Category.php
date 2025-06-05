<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Stevebauman\Inventory\Traits\CategoryTrait;
use Baum\Node;

/**
 * Class Category.
 */
class Category extends Node
{
    use CategoryTrait;

    protected $table = 'categories';

    protected $fillable = [
        'name',
    ];

    protected $scoped = ['belongs_to'];

    /**
     * The hasMany inventories relationship.
     *
     * @return HasMany
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(config('inventory.models.inventory'), 'category_id', 'id');
    }
}
