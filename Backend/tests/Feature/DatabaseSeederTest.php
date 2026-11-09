<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    public function test_warhammer_catalog_and_sales_are_consistent(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);
        $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true])->assertSuccessful();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(
            ['imperium', 'caos', 'xenos', 'pintura-e-acessorios'],
            DB::table('categories')->orderBy('id')->pluck('slug')->all(),
        );
        $this->assertDatabaseCount('products', 8);
        $this->assertDatabaseHas('products', ['name' => 'Space Marines: Intercessors']);
        $this->assertDatabaseHas('products', ['name' => 'Death Guard: Plague Marines', 'stock' => 0, 'is_active' => false]);
        $this->assertDatabaseCount('cart_items', 4);
        $this->assertDatabaseCount('orders', 3);
        $this->assertDatabaseCount('order_items', 4);
        $this->assertDatabaseCount('payments', 3);

        foreach (DB::table('products')->get() as $product) {
            $this->assertNotFalse(filter_var($product->image_url, FILTER_VALIDATE_URL));
            $path = public_path(parse_url($product->image_url, PHP_URL_PATH));
            $this->assertFileExists($path);
            $image = getimagesize($path);
            $this->assertNotFalse($image);
            $this->assertSame('image/jpeg', $image['mime']);
            $this->assertGreaterThanOrEqual(300, $image[0]);
            $this->assertGreaterThanOrEqual(300, $image[1]);
        }

        $cents = fn ($amount) => (int) round((float) $amount * 100);
        foreach (DB::table('order_items')->get() as $item) {
            $product = DB::table('products')->find($item->product_id);
            $this->assertSame($product->name, $item->product_name);
            $this->assertSame($cents($product->price), $cents($item->unit_price));
            $this->assertSame($cents($item->unit_price) * $item->quantity, $cents($item->subtotal));
        }

        foreach (DB::table('orders')->get() as $order) {
            $subtotal = DB::table('order_items')->where('order_id', $order->id)->sum('subtotal');
            $payment = DB::table('payments')->where('order_id', $order->id)->first();
            $address = DB::table('addresses')->find($order->address_id);
            $this->assertSame($cents($subtotal), $cents($order->subtotal));
            $this->assertSame($cents($order->subtotal) + $cents($order->shipping_cost), $cents($order->total));
            $this->assertSame($cents($order->total), $cents($payment->amount));
            $this->assertSame($order->status, $payment->status);
            $this->assertSame($order->users_id, $address->users_id);
        }
    }
}
