<?php

namespace Stevebauman\Inventory\Traits;

use Exception;
use Stevebauman\Inventory\Exceptions\InvalidLocationException;
use Illuminate\Support\Facades\Lang;
use Stevebauman\Inventory\Models\Location;

/**
 * Trait LocationTrait.
 */
trait LocationTrait
{
    /**
     * Returns a location depending on the specified argument. If an object is supplied, it is checked if it
     * is an instance of the model Location, if a numeric value is entered, it is retrieved by its ID.
     *
     * @param Location|int $location
     *
     * @return Location|null
     * @throws InvalidLocationException
     */
    public function getLocation(Location|int $location): Location|null
    {
        if ($this->isLocation($location)) {
            return $location;
        } else if (is_numeric($location)) {
            try {
                return Location::query()->where('id', '=', $location)->first();
            } catch (Exception) {
                $message = Lang::get('inventory::exceptions.InvalidLocationException', [
                    'location' => $location,
                ]);

                throw new InvalidLocationException($message);
            }            
        } else {
            $message = Lang::get('inventory::exceptions.InvalidLocationException', [
                'location' => $location,
            ]);

            throw new InvalidLocationException($message);
        }
    }

    /**
     * Returns true or false if the specified location is an instance of a model.
     *
     * @param mixed $object
     *
     * @return bool
     */
    private function isLocation(mixed $object): bool
    {
        return is_subclass_of($object, 'Illuminate\Database\Eloquent\Model');
    }
}
