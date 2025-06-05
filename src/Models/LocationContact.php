<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LocationContact.
 */
class LocationContact extends BaseModel
{
    protected $table = 'location_contacts';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'fax',
        'type'
    ];

    /**
     * The belongsTo location relationship.
     *
     * @return BelongsTo
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(config('inventory.models.location'), 'location_id', 'id');
    }
}
