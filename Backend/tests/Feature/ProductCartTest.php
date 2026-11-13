<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Cart_item;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCartTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('Origin', 'http://localhost');
        $category = Category::create(['name' => 'Miniaturas', 'slug' => 'miniaturas']);
        $this->product = Product::create([
            'category_id' => $category->id, 'name' => 'Miniatura', 'description' => 'Para pintar',
            'price' => '19.90', 'stock' => 5, 'is_active' => true,
        ]);
    }

    public function test_guest_additions_are_persisted_and_sum_in_the_same_cart(): void
    {
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 2])
            ->assertSuccessful()->assertJsonPath('data.quantity', 2);
        $token = session('cart_token');
        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));

        $this->withSession(['cart_token' => $token])
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1])
            ->assertSuccessful()->assertJsonPath('data.quantity', 3);
        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('carts', ['guest_token' => $token, 'user_id' => null]);
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_client_uses_own_cart_and_ignores_submitted_cart_and_user_ids(): void
    {
        $other = Cart::create(['user_id' => User::factory()->create()->id]);
        $client = User::factory()->create();
        $cart = Cart::create(['user_id' => $client->id]);
        $this->actingAs($client)->postJson('/api/cart/items', [
            'product_id' => $this->product->id, 'quantity' => 1,
            'cart_id' => $other->id, 'user_id' => $other->user_id, 'price' => '0.01',
        ])->assertSuccessful()->assertJsonPath('data.cart_id', $cart->id);
        $this->assertDatabaseCount('carts', 2);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $other->id]);
    }

    public function test_different_guest_sessions_cannot_add_to_each_others_cart(): void
    {
        foreach (['first-guest', 'second-guest'] as $token) {
            $this->withSession(['cart_token' => $token])
                ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1])
                ->assertSuccessful()->assertJsonPath('data.quantity', 1);
        }
        $this->assertDatabaseCount('carts', 2);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_stock_is_validated_against_the_existing_quantity_without_changing_the_item(): void
    {
        $cart = Cart::create(['guest_token' => 'existing-guest']);
        Cart_item::create(['cart_id' => $cart->id, 'product_id' => $this->product->id, 'quantity' => 4]);
        $this->withSession(['cart_token' => 'existing-guest'])
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->id, 'quantity' => 4]);
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_invalid_quantities_and_unavailable_products_are_rejected_without_creating_carts(): void
    {
        foreach ([0, -1, 1.5, 6] as $quantity) {
            $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => $quantity])
                ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        }
        $this->product->update(['stock' => 0]);
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->product->update(['stock' => 5, 'is_active' => false]);
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1])->assertNotFound();
        $this->product->delete();
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1])->assertNotFound();
        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cart_totals_use_live_database_prices_and_ignore_submitted_values(): void
    {
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.total', '0.00')
            ->assertJsonPath('data.item_count', 0)->assertJsonMissingPath('data.guest_token');
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 3, 'price' => '0.01']);
        $this->product->update(['price' => '0.10']);
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.total', '0.30')
            ->assertJsonPath('data.items.0.subtotal', '0.30')->assertJsonPath('data.item_count', 3)
            ->assertJsonPath('data.items.0.product.price', '0.10');
    }

    public function test_guest_can_change_quantity_remove_with_zero_and_delete_an_unavailable_item(): void
    {
        $response = $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);
        $id = $response->json('data.id');
        $this->patchJson("/api/cart/items/{$id}", ['quantity' => 2, 'subtotal' => '0.01'])
            ->assertOk()->assertJsonPath('data.total', '39.80')->assertJsonPath('data.item_count', 2);
        $this->patchJson("/api/cart/items/{$id}", ['quantity' => 6])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->patchJson("/api/cart/items/{$id}", ['quantity' => 0])
            ->assertOk()->assertJsonPath('data.total', '0.00')->assertJsonPath('data.items', []);
        $id = $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1])->json('data.id');
        $this->product->update(['is_active' => false]);
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.items.0.available', false);
        $this->deleteJson("/api/cart/items/{$id}")->assertOk()->assertJsonPath('data.item_count', 0);
    }

    public function test_guest_and_client_cannot_read_change_or_delete_another_cart_items(): void
    {
        $other = Cart::create(['user_id' => User::factory()->create()->id]);
        $item = Cart_item::create(['cart_id' => $other->id, 'product_id' => $this->product->id, 'quantity' => 2]);
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.items', []);
        $this->patchJson("/api/cart/items/{$item->id}", ['quantity' => 1])->assertNotFound();
        $this->deleteJson("/api/cart/items/{$item->id}")->assertNotFound();
        $this->actingAs(User::factory()->create());
        $this->patchJson("/api/cart/items/{$item->id}", ['quantity' => 1])->assertNotFound();
        $this->deleteJson("/api/cart/items/{$item->id}")->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 2]);
    }

    public function test_unavailable_and_overstock_items_are_flagged_and_negative_or_fractional_updates_are_rejected(): void
    {
        $id = $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 3])->json('data.id');
        $this->product->update(['stock' => 1]);
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.items.0.available', false)
            ->assertJsonPath('data.items.0.product.stock', 1);
        $this->patchJson("/api/cart/items/{$id}", ['quantity' => 1])->assertOk()
            ->assertJsonPath('data.items.0.available', true);
        foreach ([-1, 0.5] as $quantity) {
            $this->patchJson("/api/cart/items/{$id}", ['quantity' => $quantity])->assertUnprocessable();
        }
        $this->product->update(['is_active' => false]);
        $this->patchJson("/api/cart/items/{$id}", ['quantity' => 1])->assertUnprocessable();
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.items.0.available', false);
    }

    public function test_viewing_the_cart_as_a_guest_stores_nothing_until_an_item_is_added(): void
    {
        $this->getJson('/api/cart')->assertOk()
            ->assertJsonPath('data.items', [])->assertJsonPath('data.item_count', 0)->assertJsonPath('data.total', '0.00');
        $this->assertDatabaseCount('carts', 0);
        $this->assertNull(session('cart_token'));

        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 2])->assertSuccessful();
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.item_count', 2);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_prune_removes_only_guest_carts_unused_for_a_week(): void
    {
        $old = now()->subDays(8);
        $account = Cart::create(['user_id' => User::factory()->create()->id]);
        $stale = Cart::create(['guest_token' => 'stale']);
        $active = Cart::create(['guest_token' => 'active']);
        $recent = Cart::create(['guest_token' => 'recent']);
        Cart::whereKey([$account->id, $stale->id, $active->id])->update(['created_at' => $old]);
        Cart_item::create(['cart_id' => $stale->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        Cart_item::where('cart_id', $stale->id)->update(['updated_at' => $old]);
        Cart_item::create(['cart_id' => $active->id, 'product_id' => $this->product->id, 'quantity' => 1]);

        $this->artisan('carts:prune-guests')->assertSuccessful();

        $this->assertSame([$account->id, $active->id, $recent->id], Cart::orderBy('id')->pluck('id')->all());
        $this->assertDatabaseCount('cart_items', 1);
    }
}
