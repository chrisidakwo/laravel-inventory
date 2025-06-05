## Installation

### Installation (Laravel 9 and above)

This package can only work in Laravel projects running Laravel v9 and above.

For project running a lesser version, you can use the parent project: https://github.com/mauricecalhoun/inventory 

#### Steps:

1. Add inventory to your `composer.json` file:

    "chrisidakwo/laravel-inventory" : "dev-master"

2. Now perform a `composer update` on your project's source.

3. Then insert the service provider in your `config/app.php` config file:

    'Stevebauman\Inventory\InventoryServiceProvider'

4. Either publish the assets to customize the database tables using:

    php artisan vendor:publish

5. And then run the migrations:

    php artisan migrate

6. Or use the inventory install command:

    php artisan inventory:install

## Customize Installation

### I don't need to customize my models

If you don't need to create & customize your models, I've included pre-built models.

If you'd like to use them you'll have include them in your use statements:

    use Stevebauman\Inventory\Models\Inventory;

    class InventoryController extends BaseController
    {
        /*
        * Holds the inventory model
        *
        * @var Inventory
        */
        protected $inventory;

        public function __construct(Inventory $inventory)
        {
            $this->inventory = $inventory;
        }

        public function create()
        {
            $item = new $this->inventory;

            // etc...
        }
    }

### I want to customize my models

Create the models, but keep in mind the models need:

- The shown fillable attribute arrays (needed for dynamically creating & updating relationship records)
- The shown relationship names (such as `stocks()`)
- The shown relationship type (such as hasOne, hasMany, belongsTo etc)

You are free to modify anything else (such as table names, model names, namespace etc!).

Metric:

    class Metric extends Model
    {
        protected $table = 'metrics';
    }

Location:

    use Baum\Node;

    class Location extends Node
    {
        protected $table = 'locations';
    }

Category:

    use Stevebauman\Inventory\Traits\CategoryTrait;
    use Baum\Node;

    class Category extends Node
    {
        protected $table = 'categories';

        protected $scoped = ['belongs_to'];

        public function inventories()
        {
            return $this->hasMany('Inventory', 'category_id');
        }
    }

Supplier:

    use Stevebauman\Inventory\Traits\SupplierTrait;

    class Supplier extends BaseModel
    {
        use SupplierTrait;

        protected $table = 'suppliers';

         public function items()
        {
            return $this->belongsToMany('Inventory', 'inventory_suppliers', 'supplier_id')->withTimestamps();
        }
    }

Inventory:

    use Stevebauman\Inventory\Traits\InventoryTrait;
    use Stevebauman\Inventory\Traits\InventoryVariantTrait;

    class Inventory extends Model
    {
        use InventoryTrait;
        use InventoryVariantTrait;

        protected $table = 'inventory';

        public function category()
        {
            return $this->hasOne('Category', 'id', 'category_id');
        }

        public function metric()
        {
            return $this->hasOne('Metric', 'id', 'metric_id');
        }

        public function sku()
        {
            return $this->hasOne('InventorySku', 'inventory_id', 'id');
        }

        public function stocks()
        {
            return $this->hasMany('InventoryStock', 'inventory_id');
        }

        public function suppliers()
        {
            return $this->belongsToMany('Supplier', 'inventory_suppliers', 'inventory_id')->withTimestamps();
        }
    }

InventorySku:

    use Stevebauman\Inventory\Traits\InventorySkuTrait;

    class InventorySku extends Model
    {
        use InventorySkuTrait;

        protected $table = 'inventory_skus';

        protected $fillable = array(
            'inventory_id',
            'code',
        );

        public function item()
        {
            return $this->belongsTo('Inventory', 'inventory_id', 'id');
        }
    }

InventoryStock:

    use Stevebauman\Inventory\Traits\InventoryStockTrait;

    class InventoryStock extends Model
    {
        use InventoryStockTrait;

        protected $table = 'inventory_stocks';

        protected $fillable = array(
            'inventory_id',
            'location_id',
            'quantity',
            'aisle',
            'row',
            'bin',
        );

        public function item()
        {
            return $this->belongsTo('Inventory', 'inventory_id', 'id');
        }

        public function movements()
        {
            return $this->hasMany('InventoryStockMovement', 'stock_id');
        }

        public function transactions()
        {
            return $this->hasMany('InventoryTransaction', 'stock_id', 'id');
        }

        public function location()
        {
            return $this->hasOne('Location', 'id', 'location_id');
        }
    }

InventoryStockMovement:

    use Stevebauman\Inventory\Traits\InventoryStockMovementTrait;

    class InventoryStockMovement extends Model
    {
        use InventoryStockMovementTrait;

        protected $table = 'inventory_stock_movements';

        protected $fillable = array(
            'stock_id',
            'created_by',
            'before',
            'after',
            'cost',
            'reason',
        );

        public function stock()
        {
            return $this->belongsTo('InventoryStock', 'stock_id', 'id');
        }
    }

InventoryTransaction:

    use Stevebauman\Inventory\Traits\InventoryTransactionTrait;
    use Stevebauman\Inventory\Interfaces\StateableInterface;

    class InventoryTransaction extends BaseModel implements StateableInterface
    {
        use InventoryTransactionTrait;

        protected $table = 'inventory_transactions';

        protected $fillable = array(
            'created_by',
            'stock_id',
            'name',
            'state',
            'quantity',
        );

        public function stock()
        {
            return $this->belongsTo('InventoryStock', 'stock_id', 'id');
        }

        public function histories()
        {
            return $this->hasMany('InventoryTransactionHistory', 'transaction_id', 'id');
        }
    }

InventoryTransactionHistory:

    use Stevebauman\Inventory\Traits\InventoryTransactionHistoryTrait;

    class InventoryTransactionHistory extends BaseModel
    {
        use InventoryTransactionHistoryTrait;

        protected $table = 'inventory_transaction_histories';

        protected $fillable = array(
            'created_by',
            'transaction_id',
            'state_before',
            'state_after',
            'quantity_before',
            'quantity_after',
        );

        public function transaction()
        {
            return $this->belongsTo('InventoryTransaction', 'transaction_id', 'id');
        }
    }
