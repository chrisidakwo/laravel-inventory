<?php

namespace Stevebauman\Inventory\Models;

use Baum\Node;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Location.
 */
class Location extends Node
{
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'code',
        'address_1',
        'address_2',
        'city',
        'state_province',
        'postal_code',
        'county',
        'district',
        'country'
    ];

    protected $scoped = ['belongs_to'];

    /**
     * The hasMany stocks relationship.
     *
     * @return HasMany
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(config('inventory.models.inventory_stock'), 'location_id', 'id');
    }

    /**
     * The hasMany locationContacts relationship.
     *
     * @return HasMany
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(LocationContact::class, 'location_id', 'id');
    }
}
