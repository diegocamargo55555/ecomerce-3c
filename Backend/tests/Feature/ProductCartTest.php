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
}
