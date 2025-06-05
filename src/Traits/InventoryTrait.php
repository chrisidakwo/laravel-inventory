<?php

namespace Stevebauman\Inventory\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stevebauman\Inventory\Exceptions\InvalidLocationException;
use Stevebauman\Inventory\Exceptions\InvalidQuantityException;
use Stevebauman\Inventory\Exceptions\InvalidSupplierException;
use Stevebauman\Inventory\Exceptions\NotEnoughStockException;
use Stevebauman\Inventory\Exceptions\StockNotFoundException;
use Stevebauman\Inventory\Exceptions\StockAlreadyExistsException;
use Stevebauman\Inventory\Exceptions\IsParentException;
use Stevebauman\Inventory\InventoryServiceProvider;
use Stevebauman\Inventory\Models\Inventory;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Supplier;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

/**
 * Trait InventoryTrait.
 */
trait InventoryTrait
{
    /*
     * Location helper functions
     */
    use LocationTrait;

    /*
     * Verification helper functions
     */
    use VerifyTrait;

    /*
     * Sets the model's constructor method to automatically assign the
     * created_by attribute to the current logged-in user
     */
    use UserIdentificationTrait;

    /*
     * Helpers for starting database transactions
     */
    use DatabaseTransactionTrait;

    /**
     * The hasOne category relationship.
     *
     * @return HasOne
     */
    abstract public function category(): HasOne;

    /**
     * The hasOne metric relationship.
     *
     * @return HasOne
     */
    abstract public function metric(): HasOne;

    /**
     * The hasMany stocks relationship.
     *
     * @return HasMany
     */
    abstract public function stocks(): HasMany;

    /**
     * The belongsToMany suppliers relationship.
     *
     * @return BelongsToMany
     */
    abstract public function suppliers(): BelongsToMany;

    /**
     * The belongsToMany supplier SKU relationship.
     *
     * @return HasMany
     */
    abstract public function supplierSKUs(): HasMany;

    /**
     * The hasManyThrough attributes relationship.
     * 
     * @return BelongsToMany
     */
    abstract public function customAttributes(): BelongsToMany;

    /**
     * Overrides the models boot function to set the user
     * ID automatically to every new record.
     */
    public static function bootInventoryTrait(): void
    {
        /*
         * Assign the current users ID while the item
         * is being created
         */
        static::creating(function (Model $record) {
            $record->created_by = static::getCurrentUserId();
        });
    }

    /**
     * Returns an item record by the specified supplier's SKU code.
     *
     * @param string $sku
     *
     * @return Model
     * @throws Exception
     */
    public static function findBySku(string $sku): Model
    {
        throw new Exception('Not fully implemented');

//        /*
//         * Create a new static instance
//         */
//        $instance = new static();
//
//        /*
//         * Try and find the SKU record
//         */
//        $sku = $instance
//            ->supplierSKUs()
//            ->getRelated()
//            ->with('item')
//            ->where('code', $sku)
//            ->first();
//
//        /*
//         * Check if the SKU was found, and if an item is
//         * attached to the SKU we'll return it
//         */
//        if ($sku && $sku->item) {
//            return $sku->item;
//        }
//
//        /*
//         * Return false on failure
//         */
//        return false;
    }

    /**
     * Returns the total sum of the current item stock.
     *
     * @return int|float
     */
    public function getTotalStock(): float|int
    {
        return $this->stocks->sum('quantity');
    }

    /**
     * Returns true/false if the inventory has a metric present.
     *
     * @return bool
     */
    public function hasMetric(): bool
    {
        if ($this->metric) {
            return true;
        }

        return false;
    }

    /**
     * Returns true/false if the current item has an SKU.
     *
     * @return bool
     */
    public function hasSku(): bool
    {
        if ($this->sku) {
            return true;
        }

        return false;
    }

    /**
     * Returns true/false if the current item has a category.
     *
     * @return bool
     */
    public function hasCategory(): bool
    {
        if ($this->category) {
            return true;
        }

        return false;
    }

    /**
     * Returns the inventory's metric symbol.
     *
     * @return string|null
     */
    public function getMetricSymbol(): ?string
    {
        if ($this->hasMetric()) {
            return $this->metric->symbol;
        }

        return null;
    }

    /**
     * Returns true/false if the inventory has stock.
     *
     * @return bool
     */
    public function isInStock(): bool
    {
        return $this->getTotalStock() > 0;
    }

    /**
     * Creates a stock record to the current inventory item.
     *
     * @param float|int|string $quantity
     * @param $location
     * @param string $reason
     * @param float|int|string $cost
     * @param null $aisle
     * @param null $row
     * @param null $bin
     *
     * @return false|InventoryStock
     * @throws InvalidLocationException
     * @throws InvalidQuantityException
     * @throws IsParentException
     * @throws StockAlreadyExistsException
     */
    public function createStockOnLocation(float|int|string $quantity, $location, string $reason = '', float|int|string $cost = 0, $aisle = null, $row = null, $bin = null): false|InventoryStock
    {
        if (!$this->is_parent) {

            $location = $this->getLocation($location);
    
            try {
                /*
                 * We want to make sure stock doesn't exist on the specified location already
                 */
                if ($this->getStockFromLocation($location)) {
                    $message = Lang::get('inventory::exceptions.StockAlreadyExistsException', [
                        'location' => $location->name,
                    ]);
    
                    throw new StockAlreadyExistsException($message);
                }
            } catch (StockNotFoundException $e) {
                /*
                 * A stock record wasn't found on this location, we'll create one
                 */
                $insert = [
                    'inventory_id' => $this->getKey(),
                    'location_id' => $location->getKey(),
                    'quantity' => 0,
                    'aisle' => $aisle,
                    'row' => $row,
                    'bin' => $bin,
                ];

                /**
                 * We'll perform a "create" so a 'first' movement is generated
                 *
                 * @var InventoryStock $stock
                 */
                $stock = $this->stocks()->create($insert);
    
                /*
                 * Now we'll 'put' the inserted quantity onto the generated stock
                 * and return the results
                 */
                return $stock->put($quantity, $reason, $cost);
            }
    
            return false;
        } else {
            $message = Lang::get('inventory::exceptions.IsParentException', [
                'parentName' => $this->name,
            ]);

            throw new IsParentException($message);
        }
    }

    /**
     * Instantiates a new stock on the specified
     * location on the current item.
     *
     * @param $location
     *
     * @return InventoryStock|null
     * @throws InvalidLocationException
     * @throws IsParentException
     * @throws StockAlreadyExistsException
     */
    public function newStockOnLocation($location): ?InventoryStock
    {
        if (!$this->is_parent) {
            $location = $this->getLocation($location);
    
            try {
                /*
                 * We want to make sure stock doesn't exist on the specified location already
                 */
                if ($this->getStockFromLocation($location)) {
                    $message = Lang::get('inventory::exceptions.StockAlreadyExistsException', [
                        'location' => $location->name,
                    ]);
    
                    throw new StockAlreadyExistsException($message);
                }
            } catch (StockNotFoundException $e) {
                /*
                 * Create a new stock model instance
                 */
                $stock = $this->stocks()->getRelated();
    
                /*
                 * Assign the known attributes
                 * so devs don't have to
                 */
                $stock->inventory_id = $this->getKey();
                $stock->location_id = $location->getKey();
    
                return $stock;
            }
        } else {
            $message = Lang::get('inventory::exceptions.IsParentException', [
                'parentName' => $this->name,
            ]);

            throw new IsParentException($message);
        }

        return null;
    }

    /**
     * Takes the specified amount ($quantity) of stock from specified stock location.
     *
     * @param float|int|string $quantity
     * @param $location
     * @param string $reason
     *
     * @return bool|array|InventoryTrait
     * @throws InvalidLocationException
     * @throws InvalidQuantityException
     * @throws StockNotFoundException
     * @throws NotEnoughStockException
     */
    public function takeFromLocation(float|int|string $quantity, $location, string $reason = ''): bool|array|static
    {
        /*
         * If the specified location is an array, we must be taking from
         * multiple locations
         */
        if (is_array($location)) {
            return $this->takeFromManyLocations($quantity, $location, $reason);
        } else {
            $stock = $this->getStockFromLocation($location);

            if ($stock->take($quantity, $reason)) {
                return $this;
            }
        }

        return false;
    }

    /**
     * Takes the specified amount ($quantity) of stock from the specified stock locations.
     *
     * @param float|int|string $quantity
     * @param array $locations
     * @param string $reason
     *
     * @return array
     * @throws InvalidLocationException
     * @throws InvalidQuantityException
     * @throws NotEnoughStockException
     * @throws StockNotFoundException
     */
    public function takeFromManyLocations(float|int|string $quantity, array $locations = [], string $reason = ''): array
    {
        $stocks = [];

        foreach ($locations as $location) {
            $stock = $this->getStockFromLocation($location);

            $stocks[] = $stock->take($quantity, $reason);
        }

        return $stocks;
    }

    /**
     * Alias for the `take` function.
     *
     * @param float|int|string $quantity
     * @param $location
     * @param string $reason
     *
     * @return array|bool|Inventory|InventoryTrait
     * @throws InvalidLocationException
     * @throws InvalidQuantityException
     * @throws NotEnoughStockException
     * @throws StockNotFoundException
     */
    public function removeFromLocation(float|int|string $quantity, $location, string $reason = ''): Inventory|bool|array|static
    {
        return $this->takeFromLocation($quantity, $location, $reason);
    }

    /**
     * Alias for the `takeFromMany` function.
     *
     * @param int|float|string $quantity
     * @param array $locations
     * @param string $reason
     *
     * @return array
     * @throws InvalidLocationException
     * @throws InvalidQuantityException
     * @throws NotEnoughStockException
     * @throws StockNotFoundException
     */
    public function removeFromManyLocations($quantity, array $locations = [], string $reason = ''): array
    {
        return $this->takeFromManyLocations($quantity, $locations, $reason);
    }

    /**
     * Puts the specified amount ($quantity) of stock into the specified stock location(s).
     *
     * @param float|int|string $quantity
     * @param $location
     * @param string $reason
     * @param int $cost
     *
     * @return array|static|false
     * @throws InvalidLocationException
     * @throws InvalidQuantityException
     * @throws StockNotFoundException
     */
    public function putToLocation(float|int|string $quantity, $location, string $reason = '', int $cost = 0): array|static|false
    {
        if (is_array($location)) {
            return $this->putToManyLocations($quantity, $location);
        } else {
            $stock = $this->getStockFromLocation($location);

            if ($stock->put($quantity, $reason, $cost)) {
                return $this;
            }
        }

        return false;
    }

    /**
     * Puts the specified amount ($quantity) of stock into the specified stock locations.
     *
     * @param float|int|string $quantity
     * @param array $locations
     * @param string $reason
     * @param float|int|string $cost
     *
     * @return array
     * @throws StockNotFoundException
     * @throws InvalidLocationException
     * @throws InvalidQuantityException
     *
     */
    public function putToManyLocations(float|int|string $quantity, array $locations = [], string $reason = '', float|int|string $cost = 0): array
    {
        $stocks = [];

        foreach ($locations as $location) {
            $stock = $this->getStockFromLocation($location);

            $stocks[] = $stock->put($quantity, $reason, $cost);
        }

        return $stocks;
    }

    /**
     * Alias for the `put` function.
     *
     * @param float|int|string $quantity
     * @param $location
     * @param string $reason
     * @param float|int|string $cost
     *
     * @return array
     * @throws InvalidLocationException
     * @throws InvalidQuantityException
     * @throws StockNotFoundException
     */
    public function addToLocation(float|int|string $quantity, $location, string $reason = '', float|int|string $cost = 0): array
    {
        return $this->putToLocation($quantity, $location, $reason, $cost);
    }

    /**
     * Alias for the `putToMany` function.
     *
     * @param float|int|string $quantity
     * @param array $locations
     * @param string $reason
     * @param float|int|string $cost
     *
     * @return array
     * @throws InvalidLocationException
     * @throws InvalidQuantityException
     * @throws StockNotFoundException
     */
    public function addToManyLocations(float|int|string $quantity, array $locations = [], string $reason = '', float|int|string $cost = 0): array
    {
        return $this->putToManyLocations($quantity, $locations, $reason, $cost);
    }

    /**
     * Moves a stock from one location to another.
     *
     * @param $fromLocation
     * @param $toLocation
     *
     * @return bool
     * @throws InvalidLocationException
     *
     * @throws StockNotFoundException
     */
    public function moveStock($fromLocation, $toLocation): bool
    {
        $stock = $this->getStockFromLocation($fromLocation);

        $toLocation = $this->getLocation($toLocation);

        return $stock->moveTo($toLocation);
    }

    /**
     * Retrieves an inventory stock from a given location.
     *
     * @param $location
     *
     * @throws InvalidLocationException
     * @throws StockNotFoundException
     *
     * @return InventoryStock
     */
    public function getStockFromLocation($location): InventoryStock
    {
        $location = $this->getLocation($location);

        $stock = $this->stocks()
            ->where('inventory_id', $this->getKey())
            ->where('location_id', $location->getKey())
            ->first();

        if ($stock) {
            return $stock;
        } else {
            $message = Lang::get('inventory::exceptions.StockNotFoundException', [
                'location' => $location->name,
            ]);

            throw new StockNotFoundException($message);
        }
    }

    /**
     * Returns the item's SKU.
     *
     * @return null|string
     */
    public function getSku(): ?string
    {
        if ($this->hasSku()) {
            return $this->sku;
        }

        return null;
    }

    /**
     * Adds all the specified suppliers inside
     * the array to the current inventory item.
     *
     * @param array $suppliers
     *
     * @return bool
     * @throws InvalidSupplierException
     * @throws IsParentException
     */
    public function addSuppliers(array $suppliers = []): bool
    {
        if (!$this->is_parent) {
            foreach ($suppliers as $supplier) {
                $this->addSupplier($supplier);
            }
    
            return true;
        } else {
            $message = Lang::get('inventory::exceptions.IsParentException', [
                'parentName' => $this->name,
            ]);

            throw new IsParentException($message);
        }
    }

    /**
     * Removes all suppliers from the current item.
     *
     * @return bool
     * @throws InvalidSupplierException
     */
    public function removeAllSuppliers(): bool
    {
        $suppliers = $this->suppliers()->get();

        foreach ($suppliers as $supplier) {
            $this->removeSupplier($supplier);
        }

        return true;
    }

    /**
     * Removes all the specified suppliers inside
     * the array from the current inventory item.
     *
     * @param array $suppliers
     *
     * @return bool
     * @throws InvalidSupplierException
     */
    public function removeSuppliers(array $suppliers = []): bool
    {
        foreach ($suppliers as $supplier) {
            $this->removeSupplier($supplier);
        }

        return true;
    }

    /**
     * Adds the specified supplier to the current inventory item.
     *
     * @param Supplier|int|string $supplier
     *
     * @return bool
     * @throws InvalidSupplierException
     * @throws IsParentException
     */
    public function addSupplier(Supplier|int|string $supplier): bool
    {
        if (!$this->is_parent) {
            $supplier = $this->getSupplier($supplier);
    
            return $this->processSupplierAttach($supplier);
        } else {
            $message = Lang::get('inventory::exceptions.IsParentException', [
                'parentName' => $this->name,
            ]);

            throw new IsParentException($message);
        }
    }

    /**
     * Removes the specified supplier from the current inventory item.
     *
     * @param Supplier|int|string $supplier
     *
     * @throws InvalidSupplierException
     *
     * @return bool
     */
    public function removeSupplier(Supplier|int|string $supplier): bool
    {
        $supplier = $this->getSupplier($supplier);

        return $this->processSupplierDetach($supplier);
    }

    /**
     * Retrieves a supplier from the specified variable.
     *
     * @param Supplier|int|string $supplier
     *
     * @return Supplier|null
     *
     * @throws InvalidSupplierException
     */
    public function getSupplier(Supplier|int|string $supplier): Supplier|null
    {
        if ($this->isNumeric($supplier)) {
            return $this->getSupplierById($supplier);
        } elseif ($this->isModel($supplier)) {
            return $supplier;
        } else {
            $message = Lang::get('inventory::exceptions.InvalidSupplierException', [
                'supplier' => $supplier,
            ]);

            throw new InvalidSupplierException($message);
        }
    }

    /**
     * @throws InvalidSupplierException
     */
    public function addSupplierSKU(Supplier|int|string $supplier, $sku): void
    {
        $supplierModel = $this->resolveSupplier($supplier);

        $this->supplierSKUs()->updateOrCreate(['supplier_id'=>$supplierModel->id], ['supplier_id' => $supplierModel->id, 'supplier_sku' => $sku]);
    }

    /**
     * TODO:
     * Retrieves the sku code corresponding to this inventory item
     * for the given supplier
     *
     * @param mixed $supplier
     *
     * @return string
     * @throws InvalidSupplierException
     */
    public function getSupplierSKU(Supplier|int|string $supplier): string
    {
        $supplierModel = $this->resolveSupplier($supplier);
        $sku = $this->supplierSKUs->where('supplier_id', $supplierModel->id)->first();
        
        return $sku->supplier_sku;
    }

    /**
     * @throws InvalidSupplierException
     */
    public function updateSupplierSKU(Supplier|int|string $supplier, $sku) {
        $supplierModel = $this->resolveSupplier($supplier);

        return $this->supplierSKUs()->updateOrCreate(
            ['supplier_id'=>$supplierModel->id],
            [
                'supplier_id' => $supplierModel->id,
                'supplier_sku' => $sku
            ]
        );
    }

    /**
     * Resolves the supplier model based on a supplier id
     * or just returns the model
     *
     * @param mixed $supplier
     * 
     * @return Supplier
     * 
     * @throws InvalidSupplierException
     */
    private function resolveSupplier(Supplier|int|string $supplier): Supplier
    {
        $s = null;
        if ($this->isNumeric($supplier)) {
            $s = $this->getSupplierById($supplier);
        } elseif ($this->isModel($supplier)) {
            $s = $supplier;
        } elseif (is_string($supplier)) {
            $s = $this->suppliers->where('code', $supplier)->first();
        } 
        
        if(is_null($s)) {
            $message = "Supplier not found when attempting to resolve " . $supplier;

            throw new InvalidSupplierException($message);
        }

        return $s;
    }

    /**
     * Processes updating the specified SKU
     * record with the specified code.
     *
     * @param Model  $sku
     * @param string $code
     *
     * @return Model|false
     */
    private function processSkuUpdate(Model $sku, string $code): Model|false
    {
        $this->dbStartTransaction();

        try {
            if ($sku->update(compact('code'))) {
                $this->dbCommitTransaction();

                return $sku;
            }
        } catch (Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes attaching a supplier to an inventory item.
     *
     * @param Model $supplier
     *
     * @return bool
     */
    private function processSupplierAttach(Model $supplier): bool
    {
        $this->dbStartTransaction();

        try {
            $this->suppliers()->attach($supplier);

            $this->dbCommitTransaction();

            $this->fireEvent('inventory.supplier.attached', [
                'item' => $this,
                'supplier' => $supplier,
            ]);

            return true;
        } catch (Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes detaching a supplier.
     *
     * @param Model $supplier
     *
     * @return bool
     */
    private function processSupplierDetach(Model $supplier): bool
    {
        $this->dbStartTransaction();

        try {
            $this->suppliers()->detach($supplier);

            $this->dbCommitTransaction();

            $this->fireEvent('inventory.supplier.detached', [
                'item' => $this,
                'supplier' => $supplier,
            ]);

            return true;
        } catch (Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Returns a supplier by the specified ID.
     *
     * @param Model|array|int|string $id
     *
     * @return Supplier|null
     */
    private function getSupplierById(Model|array|int|string $id): ?Supplier
    {
        return $this->suppliers()->find($id);
    }

    /**
     * Returns the configuration option for the
     * enablement of automatic SKU generation.
     *
     * @return bool
     */
    private function skusEnabled(): bool
    {
        return Config::get('inventory'.InventoryServiceProvider::$packageConfigSeparator.'skus_enabled', false);
    }
}
