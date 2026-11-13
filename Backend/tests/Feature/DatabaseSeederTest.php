<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_cover_required_scenarios_and_keep_totals_consistent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('products', 30);
        $this->assertDatabaseCount('categories', 4);
        $this->assertSame(3, User::where('role', 'cliente')->count());
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertTrue(Product::where('is_active', false)->exists());
        $this->assertTrue(Product::where('is_active', true)->where('stock', 0)->exists());
        $this->assertDatabaseCount('carts', 4);
        foreach (DB::table('carts')->get() as $cart) {
            $this->assertTrue(DB::table('cart_items')->where('cart_id', $cart->id)->exists());
            if ($cart->user_id === null) {
                $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $cart->guest_token);
            }
        }
        $this->assertEqualsCanonicalizing(['pendente', 'pago', 'enviado', 'entregue', 'cancelado'], Order::pluck('status')->all());

        foreach (DB::table('categories')->pluck('image_url') as $url) {
            $this->assertFileExists(public_path($url));
        }
        foreach (Product::all() as $product) {
            $this->assertFileExists(public_path(parse_url($product->image_url, PHP_URL_PATH)));
        }
        foreach (Order::with('items', 'payments')->get() as $order) {
            $this->assertNotEmpty($order->items);
            $subtotal = 0;
            foreach ($order->items as $item) {
                $itemTotal = $this->cents($item->unit_price) * $item->quantity;
                $this->assertSame($itemTotal, $this->cents($item->subtotal));
                $this->assertNotEmpty($item->product_name);
                $subtotal += $itemTotal;
            }
            $this->assertSame($subtotal, $this->cents($order->subtotal));
            $this->assertSame($subtotal + $this->cents($order->shipping_cost), $this->cents($order->total));
            $this->assertCount(1, $order->payments);
            $payment = $order->payments->first();
            $this->assertSame($this->cents($order->total), $this->cents($payment->amount));
            $paid = in_array($order->status, ['pago', 'enviado', 'entregue']);
            $this->assertSame($paid ? 'pago' : $order->status, $payment->status);
            $this->assertSame($paid, $payment->paid_at !== null);
        }
    }

    public function test_repeating_seeders_preserves_existing_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $product = Product::first();
        $product->update(['price' => '123.45', 'stock' => 7]);
        $user = User::where('email', 'test@example.com')->first();
        $user->update(['name' => 'Nome preservado', 'password' => 'outra-senha']);
        $password = $user->password;
        $guestToken = DB::table('carts')->whereNull('user_id')->value('guest_token');
        $before = [];
        foreach (['users', 'categories', 'products', 'addresses', 'carts', 'cart_items', 'orders', 'order_items', 'payments'] as $table) {
            $before[$table] = DB::table($table)->count();
        }

        $this->seed(DatabaseSeeder::class);

        foreach ($before as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame('123.45', $product->fresh()->price);
        $this->assertSame(7, $product->fresh()->stock);
        $this->assertSame('Nome preservado', $user->fresh()->name);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame($guestToken, DB::table('carts')->whereNull('user_id')->value('guest_token'));
        $this->assertSame(30, Product::count());
    }

    private function cents(string $value): int
    {
        [$whole, $fraction] = explode('.', $value.'.00');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
}
