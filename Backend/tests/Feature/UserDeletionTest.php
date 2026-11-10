<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('Origin', 'http://localhost');
        $this->admin = User::factory()->create();
        $this->admin->role = 'admin';
        $this->admin->save();
    }

    private function address(User $user): Address
    {
        return Address::create([
            'users_id' => $user->id, 'recipient_name' => 'Cliente', 'zip_code' => '01001000', 'street' => 'Rua A',
            'number' => '10', 'neighborhood' => 'Centro', 'city' => 'São Paulo', 'state' => 'SP',
        ]);
    }

    private function product(int $stock): Product
    {
        $category = Category::create(['name' => 'Miniaturas', 'slug' => 'miniaturas']);

        return Product::create([
            'category_id' => $category->id, 'name' => 'Marine', 'description' => 'Para pintar',
            'price' => '10.00', 'stock' => $stock, 'is_active' => true,
        ]);
    }

    public function test_admin_deletes_a_user_together_with_cart_and_addresses(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $this->product(5)->id, 'quantity' => 1]);
        $this->address($user);

        $this->actingAs($this->admin)->deleteJson("/api/users/{$user->id}")->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_user_with_orders_is_kept_with_a_clear_conflict(): void
    {
        $user = User::factory()->create();
        Cart::create(['user_id' => $user->id]);
        Order::create([
            'users_id' => $user->id, 'address_id' => $this->address($user)->id, 'status' => 'pago',
            'subtotal' => '10.00', 'shipping_cost' => '20.00', 'total' => '30.00',
        ]);

        $this->actingAs($this->admin)->deleteJson("/api/users/{$user->id}")
            ->assertConflict()->assertJsonPath('message', "Não é possível excluir {$user->name}: há pedidos registrados nesta conta.");

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_admin_cannot_delete_the_own_account(): void
    {
        $this->actingAs($this->admin)->deleteJson("/api/users/{$this->admin->id}")->assertConflict();

        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'role' => 'admin']);
    }

    public function test_unpaid_orders_older_than_thirty_minutes_are_cancelled_and_return_stock(): void
    {
        $user = User::factory()->create();
        $address = $this->address($user);
        $product = $this->product(5);
        $ids = [];
        foreach ([31, 29] as $minutesAgo) {
            $this->travelTo(now()->subMinutes($minutesAgo));
            $this->actingAs($user)->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertSuccessful();
            $ids[] = $this->postJson('/api/orders', ['address_id' => $address->id])->assertCreated()->json('data.id');
            $this->travelBack();
        }
        $this->postJson("/api/orders/{$ids[1]}/pay")->assertOk();
        $this->travelTo(now()->subMinutes(40));
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertSuccessful();
        $oldPaid = $this->postJson('/api/orders', ['address_id' => $address->id])->assertCreated()->json('data.id');
        $this->postJson("/api/orders/{$oldPaid}/pay")->assertOk();
        $this->travelBack();

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();
        $this->artisan('orders:cancel-unpaid')->assertSuccessful();

        $this->assertDatabaseHas('orders', ['id' => $ids[0], 'status' => 'cancelado']);
        $this->assertDatabaseHas('payments', ['order_id' => $ids[0], 'status' => 'cancelado']);
        $this->assertDatabaseHas('orders', ['id' => $ids[1], 'status' => 'pago']);
        $this->assertDatabaseHas('orders', ['id' => $oldPaid, 'status' => 'pago']);
        $this->assertSame(2, $product->fresh()->stock);
    }
}
