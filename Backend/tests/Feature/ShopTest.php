<?php

namespace Tests\Feature;

use App\Models\{Address, Cart, Category, Order, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $other;
    private User $admin;
    private Category $category;
    private Product $product;
    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('Origin', 'http://localhost');
        $this->customer = User::factory()->create();
        $this->other = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
        $this->category = Category::create(['name' => 'Eletrônicos', 'slug' => 'eletronicos']);
        $this->product = Product::create([
            'category_id' => $this->category->id, 'name' => 'Fone Bluetooth',
            'description' => 'Fone sem fio', 'price' => '199.90', 'stock' => 10, 'is_active' => true,
        ]);
        $this->address = $this->customer->addresses()->create($this->addressFields());
    }

    private function addressFields(): array
    {
        return ['recipient_name' => 'Ana Silva', 'zip_code' => '01001-000', 'street' => 'Praça da Sé',
            'number' => '10', 'complement' => null, 'neighborhood' => 'Sé', 'city' => 'São Paulo', 'state' => 'SP'];
    }

    private function fillCart(int $quantity = 2): Cart
    {
        $cart = $this->customer->carts()->create();
        $cart->items()->create(['product_id' => $this->product->id, 'quantity' => $quantity]);
        return $cart;
    }

    private function placeOrder(): int
    {
        $this->fillCart();
        return $this->actingAs($this->customer)->postJson('/api/checkout', ['address_id' => $this->address->id])
            ->assertCreated()->json('data.id');
    }

    public function test_catalog_is_public_and_hides_inactive_and_deleted_products(): void
    {
        $inactive = $this->product->replicate();
        $inactive->is_active = false;
        $inactive->save();
        $deleted = $this->product->replicate();
        $deleted->save();
        $deleted->delete();
        $this->getJson('/api/products')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $this->product->id);
        $this->getJson('/api/products/'.$inactive->id)->assertNotFound();
        $this->getJson('/api/products/'.$deleted->id)->assertNotFound();
        $this->getJson('/api/categories')->assertOk();
        $this->getJson('/api/products/'.$this->product->id)->assertOk();
    }

    public function test_guest_cannot_access_customer_or_admin_areas(): void
    {
        foreach (['/api/cart', '/api/orders', '/api/addresses', '/api/admin/products'] as $path) {
            $this->getJson($path)->assertUnauthorized();
        }
        $this->postJson('/api/checkout', ['address_id' => $this->address->id])->assertUnauthorized();
    }

    public function test_customer_cannot_mutate_catalog_or_use_admin_routes(): void
    {
        $this->actingAs($this->customer);
        $this->getJson('/api/admin/products')->assertForbidden();
        $this->getJson('/api/admin/orders')->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
        $this->patchJson('/api/admin/products/'.$this->product->id, ['price' => 0])->assertForbidden();
        $this->postJson('/api/products', [])->assertStatus(405);
        $this->patchJson('/api/products/'.$this->product->id, ['is_active' => false])->assertStatus(405);
    }

    public function test_registration_does_not_grant_admin_and_session_can_be_restored(): void
    {
        $this->postJson('/api/register', ['name' => 'Cliente Novo', 'email' => 'novo@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123', 'is_admin' => true])
            ->assertCreated()->assertJsonPath('data.is_admin', false);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.email', 'novo@example.com');
        $this->postJson('/api/logout')->assertNoContent();
        // Clear the cached guard instance, as a new HTTP request would.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->postJson('/api/login', ['email' => $this->customer->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/login', ['email' => $this->customer->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('data.id', $this->customer->id);
    }

    public function test_cart_reuses_existing_cart_and_ignores_supplied_owner(): void
    {
        $this->actingAs($this->customer);
        $id = $this->getJson('/api/cart')->assertOk()->json('data.id');
        $this->getJson('/api/cart')->assertJsonPath('data.id', $id);
        $this->postJson('/api/carts', ['user_id' => $this->other->id])->assertJsonPath('data.id', $id);
        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseHas('carts', ['id' => $id, 'user_id' => $this->customer->id]);
    }

    public function test_customer_can_add_update_and_remove_items_without_duplicates(): void
    {
        $cart = $this->fillCart(1);
        $this->actingAs($this->customer);
        $id = $this->postJson('/api/cart-items', ['cart_id' => $cart->id, 'product_id' => $this->product->id, 'quantity' => 2])
            ->assertCreated()->assertJsonPath('data.quantity', 3)->json('data.id');
        $this->assertDatabaseCount('cart_items', 1);
        $this->patchJson('/api/cart-items/'.$id, ['quantity' => 4])->assertOk()->assertJsonPath('data.quantity', 4);
        $this->getJson('/api/cart')->assertJsonPath('data.items.0.product.name', 'Fone Bluetooth');
        $this->deleteJson('/api/cart-items/'.$id)->assertNoContent();
        $this->getJson('/api/cart')->assertJsonCount(0, 'data.items');
    }

    public function test_cart_rejects_invalid_or_excessive_quantities_and_inactive_products(): void
    {
        $cart = $this->fillCart(1);
        $this->actingAs($this->customer);
        foreach ([0, -1, 1.5, 11] as $quantity) {
            $this->postJson('/api/cart-items', ['cart_id' => $cart->id, 'product_id' => $this->product->id, 'quantity' => $quantity])->assertUnprocessable();
        }
        $this->product->update(['is_active' => false]);
        $this->postJson('/api/cart-items', ['cart_id' => $cart->id, 'product_id' => $this->product->id, 'quantity' => 1])->assertUnprocessable();
    }

    public function test_customers_cannot_read_or_change_each_others_carts_or_addresses(): void
    {
        $cart = $this->fillCart();
        $item = $cart->items()->first();
        $this->actingAs($this->other);
        $this->getJson('/api/carts')->assertJsonCount(0, 'data');
        $this->getJson('/api/cart-items')->assertJsonCount(0, 'data');
        $this->getJson('/api/addresses')->assertJsonCount(0, 'data');
        $this->getJson('/api/carts/'.$cart->id)->assertNotFound();
        $this->getJson('/api/cart-items/'.$item->id)->assertNotFound();
        $this->patchJson('/api/cart-items/'.$item->id, ['quantity' => 1])->assertNotFound();
        $this->deleteJson('/api/cart-items/'.$item->id)->assertNotFound();
        $this->postJson('/api/cart-items', ['cart_id' => $cart->id, 'product_id' => $this->product->id, 'quantity' => 1])->assertNotFound();
        $this->getJson('/api/addresses/'.$this->address->id)->assertNotFound();
        $this->patchJson('/api/addresses/'.$this->address->id, $this->addressFields())->assertNotFound();
        $this->deleteJson('/api/addresses/'.$this->address->id)->assertNotFound();
    }

    public function test_new_address_belongs_to_authenticated_customer_and_complement_is_optional(): void
    {
        $this->actingAs($this->other)->postJson('/api/addresses', [...$this->addressFields(), 'users_id' => $this->customer->id])
            ->assertCreated();
        $this->assertDatabaseHas('addresses', ['users_id' => $this->other->id, 'complement' => null]);
    }

    public function test_checkout_calculates_prices_creates_items_debits_stock_and_clears_cart(): void
    {
        $cart = $this->fillCart();
        $this->actingAs($this->customer)->postJson('/api/checkout', [
            'address_id' => $this->address->id, 'users_id' => $this->other->id, 'total' => 1, 'status' => 'pago',
        ])->assertCreated()->assertJsonPath('data.status', 'pendente')
            ->assertJsonPath('data.users_id', $this->customer->id)->assertJsonCount(1, 'data.items');
        $order = Order::firstOrFail();
        $this->assertEquals(399.80, $order->total);
        $this->assertEquals(0, $order->shipping_cost);
        $this->assertEquals(8, $this->product->fresh()->stock);
        $this->assertEquals('Fone Bluetooth', $order->items()->first()->product_name);
        $this->assertCount(0, $cart->items()->get());
    }

    public function test_duplicate_checkout_does_not_create_a_second_order_or_debit_stock_twice(): void
    {
        $this->placeOrder();
        $this->postJson('/api/checkout', ['address_id' => $this->address->id])->assertUnprocessable();
        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(8, $this->product->fresh()->stock);
    }

    public function test_checkout_is_atomic_when_one_item_is_unavailable(): void
    {
        $cart = $this->fillCart();
        $unavailable = $this->product->replicate();
        $unavailable->stock = 0;
        $unavailable->save();
        $cart->items()->create(['product_id' => $unavailable->id, 'quantity' => 1]);
        $this->actingAs($this->customer)->postJson('/api/checkout', ['address_id' => $this->address->id])->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('cart_items', 2);
        $this->assertEquals(10, $this->product->fresh()->stock);
    }

    public function test_checkout_rejects_empty_cart_and_address_from_another_customer(): void
    {
        $this->actingAs($this->customer)->postJson('/api/checkout', ['address_id' => $this->address->id])->assertUnprocessable();
        $this->actingAs($this->other)->postJson('/api/checkout', ['address_id' => $this->address->id])->assertNotFound();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_orders_are_private_and_legacy_writes_are_closed(): void
    {
        $id = $this->placeOrder();
        $this->actingAs($this->other)->getJson('/api/orders')->assertJsonCount(0, 'data');
        $this->getJson('/api/orders/'.$id)->assertNotFound();
        $this->postJson('/api/orders', [])->assertStatus(405);
        $this->patchJson('/api/orders/'.$id, ['status' => 'pago'])->assertStatus(405);
        $this->postJson('/api/order-items', [])->assertNotFound();
        $this->postJson('/api/payments', [])->assertNotFound();
        $this->actingAs($this->admin)->getJson('/api/admin/orders')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_manage_products_and_linked_category_cannot_be_deleted(): void
    {
        $this->product->update(['is_active' => false]);
        $this->actingAs($this->admin)->getJson('/api/admin/products')->assertJsonCount(1, 'data');
        $this->patchJson('/api/admin/products/'.$this->product->id, ['is_active' => true])->assertOk()->assertJsonPath('data.is_active', true);
        $id = $this->postJson('/api/admin/categories', ['name' => 'Acessórios', 'slug' => 'acessorios'])->assertCreated()->json('data.id');
        $this->patchJson('/api/admin/categories/'.$id, ['name' => 'Novos acessórios', 'slug' => 'acessorios'])->assertOk();
        $this->deleteJson('/api/admin/categories/'.$this->category->id)->assertUnprocessable();
        $this->deleteJson('/api/admin/categories/'.$id)->assertNoContent();
        $this->postJson('/api/admin/products', ['category_id' => $this->category->id, 'name' => 'Mouse', 'description' => 'Sem fio', 'price' => 99.90, 'stock' => 5])->assertCreated();
    }

    public function test_cancel_restores_stock_once_and_final_status_cannot_change(): void
    {
        $id = $this->placeOrder();
        $this->actingAs($this->admin)->patchJson('/api/admin/orders/'.$id, ['status' => 'cancelado'])->assertOk();
        $this->assertEquals(10, $this->product->fresh()->stock);
        $this->patchJson('/api/admin/orders/'.$id, ['status' => 'cancelado'])->assertUnprocessable();
        $this->patchJson('/api/admin/orders/'.$id, ['status' => 'pago'])->assertUnprocessable();
        $this->assertEquals(10, $this->product->fresh()->stock);
    }

    public function test_marking_paid_does_not_debit_stock_again(): void
    {
        $id = $this->placeOrder();
        $this->actingAs($this->admin)->patchJson('/api/admin/orders/'.$id, ['status' => 'pago'])->assertOk();
        $this->assertEquals(8, $this->product->fresh()->stock);
        $this->patchJson('/api/admin/orders/'.$id, ['status' => 'cancelado'])->assertUnprocessable();
    }

    public function test_address_used_by_order_is_preserved(): void
    {
        $this->placeOrder();
        $this->patchJson('/api/addresses/'.$this->address->id, $this->addressFields())->assertUnprocessable();
        $this->deleteJson('/api/addresses/'.$this->address->id)->assertUnprocessable();
    }

    public function test_make_admin_command_only_promotes_existing_accounts(): void
    {
        $this->artisan('app:make-admin', ['email' => $this->customer->email])->assertSuccessful();
        $this->assertTrue($this->customer->fresh()->is_admin);
        $this->artisan('app:make-admin', ['email' => 'missing@example.com'])->assertFailed();
    }

    public function test_same_origin_proxy_host_supports_session_authentication(): void
    {
        $this->withHeader('Host', 'localhost:5173')->withHeader('Origin', 'http://localhost:5173');
        $this->postJson('http://localhost:5173/api/login', ['email' => $this->customer->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('data.id', $this->customer->id);
    }

    public function test_registration_without_a_stateful_session_does_not_create_an_account(): void
    {
        $this->withHeader('Origin', 'https://outside.example.com');
        $this->postJson('/api/register', ['name' => 'Cliente', 'email' => 'outside@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123'])->assertStatus(419);
        $this->assertDatabaseMissing('users', ['email' => 'outside@example.com']);
    }

    public function test_canceling_a_legacy_order_does_not_inflate_stock(): void
    {
        $order = $this->customer->orders()->create(['address_id' => $this->address->id,
            'status' => 'pendente', 'subtotal' => 199.90, 'total' => 199.90, 'shipping_cost' => 0]);
        $order->items()->create(['product_id' => $this->product->id, 'product_name' => $this->product->name,
            'unit_price' => 199.90, 'quantity' => 1, 'subtotal' => 199.90]);
        $this->actingAs($this->admin)->patchJson('/api/admin/orders/'.$order->id, ['status' => 'cancelado'])->assertOk();
        $this->assertEquals(10, $this->product->fresh()->stock);
    }

    public function test_deleted_product_can_still_be_removed_from_cart(): void
    {
        $cart = $this->fillCart();
        $item = $cart->items()->first();
        $this->product->delete();
        $this->actingAs($this->customer)->getJson('/api/cart')->assertOk()->assertJsonPath('data.items.0.product', null);
        $this->deleteJson('/api/cart-items/'.$item->id)->assertNoContent();
    }
}
