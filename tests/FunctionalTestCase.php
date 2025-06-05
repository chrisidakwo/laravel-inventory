<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use Stevebauman\Inventory\Models\Inventory;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Location;
use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\Category;
use Stevebauman\Inventory\Models\Supplier;
use Stevebauman\Inventory\InventoryServiceProvider;
use Faker\Factory;

class FunctionalTestCase extends TestCase
{

    protected static $db = null;
    protected static $connection = null;

    protected static bool $migrated = false;
    
    public static $faker = null;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('inventory'.InventoryServiceProvider::$packageConfigSeparator.'allow_no_user', true);
        Config::set('inventory'.InventoryServiceProvider::$packageConfigSeparator.'skus_enabled', true);
        Config::set('inventory'.InventoryServiceProvider::$packageConfigSeparator.'sku_separator', '-');
        Config::set('inventory'.InventoryServiceProvider::$packageConfigSeparator.'sku_prefix_length', '3');
        Config::set('inventory'.InventoryServiceProvider::$packageConfigSeparator.'sku_code_length', '6');
        Config::set('inventory'.InventoryServiceProvider::$packageConfigSeparator.'rollback_cost', true);
        // $this->configureDatabase();
        // $this->migrateTables();

        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'category',
            '\Stevebauman\Inventory\Models\Category'
        );
        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'inventory',
            '\Stevebauman\Inventory\Models\Inventory'
        );
        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'inventory_sku',
            '\Stevebauman\Inventory\Models\InventorySku'
        );
        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'inventory_stock',
            '\Stevebauman\Inventory\Models\InventoryStock'
        );
        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'inventory_stock_movement',
            '\Stevebauman\Inventory\Models\InventoryStockMovement'
        );
        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'inventory_transaction',
            '\Stevebauman\Inventory\Models\InventoryTransaction'
        );
        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'inventory_transaction_history',
            '\Stevebauman\Inventory\Models\InventoryTransactionHistory'
        );
        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'location',
            '\Stevebauman\Inventory\Models\Location'
        );
        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'metric',
            '\Stevebauman\Inventory\Models\Metric'
        );
        Config::set(
            'inventory'.InventoryServiceProvider::$packageConfigSeparator.'models'.InventoryServiceProvider::$packageConfigSeparator.'supplier',
            '\Stevebauman\Inventory\Models\Supplier'
        );
    }
    
    public static function setUpBeforeClass(): void
    {
        FunctionalTestCase::$faker = Factory::create();

        // if (!FunctionalTestCase::$db) {
        //     FunctionalTestCase::configureDatabase();
        //     FunctionalTestCase::migrateTables();
        //     // Eloquent::unguard();
        // }
    }

    public static function tearDownAfterClass(): void
    {
        // FunctionalTestCase::dropTables();
        // FunctionalTestCase::$db->getConnection('default')->disconnect();
    }

    /**
     * Get package providers.
     *
     * @param  Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            'Stevebauman\Inventory\InventoryServiceProvider',
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        // if(!FunctionalTestCase::$migrated) {

            // echo "Running database migrations...\n";
            // $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/test_migrations');
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

            // FunctionalTestCase::$migrated = true;
        // }

        // $this->beforeApplicationDestroyed(function() {
        //     $this->artisan('migrate:rollback')->run();
        // });
    }

    private static function configureDatabase()
    {
        $db = FunctionalTestCase::$db;
        $connection = FunctionalTestCase::$connection;
        
        if (!$db && !$connection) {
            $db = new DB();

            $db->addConnection([
                // 'driver' => 'mysql',
                // 'database' => 'usurper_test',
                // 'username' => 'sail',
                // 'password' => 'password',
                // 'host' => '127.0.0.1',
                'driver' => 'sqlite',
                'database' => ':memory:',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => '',
            ], 'default');

            $connection = $db->getConnection('default');

            $db->bootEloquent();
    
            $db->setAsGlobal();   
        }
    }

    private static function migrateTables(): void
    {
        if (!DB::schema()->hasTable('users')) {
            DB::schema()->create('users', function ($table) {
                $table->id();
                $table->string('name');
            });

            DB::schema()->create('metrics', function ($table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('created_by')->unsigned()->nullable();
                $table->string('name');
                $table->string('symbol');

                $table->foreign('created_by')->references('id')->on('users')
                    ->onUpdate('restrict')
                    ->onDelete('set null');
            });

            DB::schema()->create('categories', function ($table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('parent_id')->nullable()->index();
                $table->integer('lft')->nullable()->index();
                $table->integer('rgt')->nullable()->index();
                $table->integer('depth')->nullable();
                $table->string('name');

                /*
                * This field is for scoping categories, use it if you
                * want to store multiple nested sets on the same table
                */
                $table->string('belongs_to')->nullable();
            });

            DB::schema()->create('locations', function ($table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('parent_id')->nullable()->index();
                $table->integer('lft')->nullable()->index();
                $table->integer('rgt')->nullable()->index();
                $table->integer('depth')->nullable();
                $table->string('name');

                /*
                * This field is for scoping categories, use it if you
                * want to store multiple nested sets on the same table
                */
                $table->string('belongs_to')->nullable();
            });

            DB::schema()->create('inventories', function ($table) {
                $table->id();
                $table->timestamps();
                $table->softDeletes();
                $table->foreignId('category_id')->unsigned()->nullable();
                $table->foreignId('created_by')->unsigned()->nullable();
                $table->foreignId('metric_id')->unsigned();
                $table->string('name');
                $table->text('description')->nullable();

                $table->foreign('category_id')->references('id')->on('categories')
                    ->onUpdate('restrict')
                    ->onDelete('set null');

                $table->foreign('created_by')->references('id')->on('users')
                    ->onUpdate('restrict')
                    ->onDelete('set null');

                $table->foreign('metric_id')->references('id')->on('metrics')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
    
            DB::schema()->create('inventory_stocks', function ($table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('created_by')->unsigned()->nullable();
                $table->foreignId('inventory_id')->unsigned();
                $table->foreignId('location_id')->unsigned();
                $table->decimal('quantity', 8, 2)->default(0);
                $table->string('aisle')->nullable();
                $table->string('row')->nullable();
                $table->string('bin')->nullable();

                /*
                * This allows only one stock to be created
                * on a single location
                */
                $table->unique(['inventory_id', 'location_id']);

                $table->foreign('created_by')->references('id')->on('users')
                    ->onUpdate('restrict')
                    ->onDelete('set null');

                $table->foreign('inventory_id')->references('id')->on('inventories')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');

                $table->foreign('location_id')->references('id')->on('locations')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });
      
            DB::schema()->create('inventory_stock_movements', function ($table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('stock_id')->unsigned();
                $table->foreignId('created_by')->unsigned()->nullable();
                $table->decimal('before', 8, 2)->default(0);
                $table->decimal('after', 8, 2)->default(0);
                $table->decimal('cost', 8, 2)->default(0)->nullable();
                $table->string('reason')->nullable();

                $table->foreign('stock_id')->references('id')->on('inventory_stocks')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');

                $table->foreign('created_by')->references('id')->on('users')
                    ->onUpdate('restrict')
                    ->onDelete('set null');
            });
        
            DB::schema()->create('suppliers', function ($table) {
                $table->id();
                $table->timestamps();

                $table->string('name');
                $table->string('code', 6)->unique();
                $table->string('address')->nullable();
                $table->string('zip_code')->nullable();
                $table->string('region')->nullable();
                $table->string('city')->nullable();
                $table->string('country')->nullable();
                $table->string('contact_title')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('contact_phone')->nullable();
                $table->string('contact_fax')->nullable();
                $table->string('contact_email')->nullable();
            });

            DB::schema()->create('inventory_suppliers', function ($table) {
                $table->id();
                $table->timestamps();
        
                $table->string('supplier_sku')->nullable();

                $table->foreignId('inventory_id')->unsigned();
                $table->foreignId('supplier_id')->unsigned();

                $table->foreign('inventory_id')->references('id')->on('inventories')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');

                $table->foreign('supplier_id')->references('id')->on('suppliers')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });

            DB::schema()->create('inventory_transactions', function ($table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('created_by')->unsigned()->nullable();
                $table->foreignId('stock_id')->unsigned();
                $table->string('name')->nullable();
                $table->string('state');
                $table->decimal('quantity', 8, 2)->default(0);

                $table->foreign('created_by')->references('id')->on('users')
                    ->onUpdate('restrict')
                    ->onDelete('set null');

                $table->foreign('stock_id')->references('id')->on('inventory_stocks')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });

            DB::schema()->create('inventory_transaction_histories', function ($table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('created_by')->unsigned()->nullable();
                $table->foreignId('transaction_id')->unsigned();

                /*
                * Allows tracking states for each transaction
                */
                $table->string('state_before');
                $table->string('state_after');

                /*
                * Allows tracking the quantities of each transaction
                */
                $table->string('quantity_before');
                $table->string('quantity_after');

                $table->foreign('created_by')->references('id')->on('users')
                    ->onUpdate('restrict')
                    ->onDelete('set null');

                $table->foreign('transaction_id')->references('id')->on('inventory_transactions')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });

            DB::schema()->table('inventories', function ($table) {
                $table->foreignId('parent_id')->unsigned()->nullable()->after('id');
    
                $table->foreign('parent_id')->references('id')->on('inventories')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            });

            DB::schema()->table('inventories', function ($table) {
                $table->boolean('is_assembly')->default(false);
                $table->boolean('is_bundle')->default(false);
                $table->boolean('is_parent')->default(false);
            });

            DB::schema()->create('inventory_assemblies', function ($table) {

                $table->id();
                $table->timestamps();
                $table->foreignId('inventory_id')->unsigned();
                $table->foreignId('part_id')->unsigned();
                $table->integer('quantity')->nullable();

                // Extra column for testing
                $table->string('extra')->nullable();

                $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
                $table->foreign('part_id')->references('id')->on('inventories')->onDelete('cascade');
            });

            DB::schema()->create('inventory_bundles', function ($table) {

                $table->id();
                $table->timestamps();
                $table->foreignId('inventory_id')->unsigned();
                $table->foreignId('component_id')->unsigned();
                $table->integer('quantity')->nullable();

                // Extra column for testing
                $table->string('extra')->nullable();

                $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
                $table->foreign('component_id')->references('id')->on('inventories')->onDelete('cascade');
            });

            DB::schema()->create('custom_attributes', function ($table) {
                $table->id();
                $table->string('name', 255);
                $table->string('display_name', 255);
                $table->string('value_type', 6)->notnull();
                $table->boolean('reserved');
                $table->boolean('required');
                $table->enum('display_type', [
                    'dropdown', 
                    'string', 
                    'currency', 
                    'decimal', 
                    'integer', 
                    'date', 
                    'time',
                    'longText',
                ]);
                $table->boolean('has_default');
                $table->string('default_value', 8191)->nullable();
                $table->string('rule', 256)->nullable();
                $table->string('rule_desc', 256)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table
                    ->timestamp('updated_at')
                    ->useCurrent()
                    ->useCurrentOnUpdate();

            });

            DB::schema()->create('custom_attribute_values', function($table) {
                $table->id();
                $table->foreignId('inventory_id');
                $table->foreignId('custom_attribute_id');
                $table->string('string_val', 8191)->nullable();
                $table->decimal('num_val', 16, 4)->nullable();  // 123,456,789,012.3456
                $table->dateTime('date_val')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('inventory_id')->references('id')->on('inventories')->onUpdate('restrict');
                $table->foreign('custom_attribute_id')->references('id')->on('custom_attributes')->onUpdate('restrict');

                $table->unique(['inventory_id', 'custom_attribute_id'], 'values_inventory_attribute_id_unique');
            });
        }
    }

    /**
     * Drops all tables previously created
     *
     * @return void
     */
    protected static function dropTables() 
    {
        DB::schema()->dropAllTables();
    }

    protected function generateSku()
    {
        return self::$faker->regexify('[A-Z0-9]{3,6}-[A-Z0-9]+-[A-Z0-9]+');
    }

    /**
     * @param array $attributes
     *
     * @return Inventory
     */
    protected function newInventory(array $attributes = [])
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        if (count($attributes) > 0) {
            return Inventory::create($attributes);
        }

        return Inventory::create([
            'metric_id' => $metric->id,
            'category_id' => $category->id,
            'sku' => $this->generateSku(),
            'name' => 'Milk',
            'description' => 'Delicious Milk',
        ]);
    }

    /**
     * @return Metric
     */
    protected function newMetric()
    {
        return Metric::create([
            'name' => 'Litres',
            'symbol' => 'L',
        ]);
    }

    /**
     * @return Location
     */
    protected function newLocation()
    {
        return Location::create([
            'name' => 'Warehouse',
            'code' => 'WH',
            'address_1' => '111 Fake St.',
            'city' => 'Bellingham',
            'state_province' => 'WA',
            'postal_code' => '98226',
            'country' => 'USA',
            'belongs_to' => '',
        ]);
    }

    /**
     * @return Category
     */
    protected function newCategory()
    {
        return Category::create([
            'name' => 'Drinks',
        ]);
    }

    /**
     * @return Supplier
     */
    protected function newSupplier()
    {
        return Supplier::create([
            'name' => 'Supplier',
            'code' => 'SP' . FunctionalTestCase::$faker->unique()->numberBetween(10, 1000),
            'address' => '123 Fake St',
            'zip_code' => '12345',
            'region' => 'ON',
            'city' => 'Toronto',
            'country' => 'Canada',
            'contact_title' => 'Manager',
            'contact_name' => 'John Doe',
            'contact_phone' => '555 555 5555',
            'contact_fax' => '555 555 5555',
            'contact_email' => 'john.doe@email.com',
        ]);
    }

    /**
     * @return InventoryStock
     */
    protected function newInventoryStock()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        $stock = new InventoryStock();
        $stock->inventory_id = $item->id;
        $stock->location_id = $location->id;
        $stock->quantity = 20;
        $stock->cost = '5.20';
        $stock->reason = 'I bought some';
        $stock->save();

        return $stock;
    }

    /**
     * @return \Stevebauman\Inventory\Models\InventoryTransaction
     */
    protected function newTransaction()
    {
        $stock = $this->newInventoryStock();

        return $stock->newTransaction();
    }
}
