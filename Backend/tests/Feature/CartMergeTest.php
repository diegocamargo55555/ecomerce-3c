<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Cart_item;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartMergeTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('Origin', 'http://localhost');
        $category = Category::create(['name' => 'Miniaturas', 'slug' => 'miniaturas']);
        $this->product = Product::create([
            'category_id' => $category->id, 'name' => 'Marine', 'description' => 'Miniatura',
            'price' => '0.10', 'stock' => 5, 'is_active' => true,
        ]);
    }

    public function test_login_merges_shared_and_exclusive_items_and_retires_the_guest_cart(): void
    {
        $user = User::factory()->create();
        $account = Cart::create(['user_id' => $user->id]);
        Cart_item::create(['cart_id' => $account->id, 'product_id' => $this->product->id, 'quantity' => 2]);
        $onlyAccount = $this->product->replicate();
        $onlyAccount->name = 'Só conta';
        $onlyAccount->save();
        Cart_item::create(['cart_id' => $account->id, 'product_id' => $onlyAccount->id, 'quantity' => 1]);
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1])->assertSuccessful();
        $onlyGuest = $this->product->replicate();
        $onlyGuest->name = 'Só visitante';
        $onlyGuest->save();
        $this->postJson('/api/cart/items', ['product_id' => $onlyGuest->id, 'quantity' => 2])->assertSuccessful();
        $token = session('cart_token');
        $oldSession = session()->getId();
        $guest = Cart::where('guest_token', $token)->firstOrFail();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('cart_warnings', [])->assertSessionMissing('cart_token');
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertDatabaseMissing('carts', ['id' => $guest->id]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $guest->id]);
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.id', $account->id)
            ->assertJsonPath('data.item_count', 6)->assertJsonPath('data.total', '0.60')->assertJsonCount(3, 'data.items');
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->assertDatabaseCount('carts', 1);
        $this->getJson('/api/cart')->assertJsonPath('data.item_count', 6);
    }

    public function test_login_caps_quantities_and_warns_about_removed_products(): void
    {
        $user = User::factory()->create();
        $account = Cart::create(['user_id' => $user->id]);
        Cart_item::create(['cart_id' => $account->id, 'product_id' => $this->product->id, 'quantity' => 4]);
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 3]);
        foreach (['inactive', 'empty'] as $state) {
            $product = $this->product->replicate();
            $product->name = $state;
            $product->save();
            $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
            match ($state) {
                'inactive' => $product->update(['is_active' => false]),
                'empty' => $product->update(['stock' => 0]),
            };
        }
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonCount(3, 'cart_warnings');
        $this->getJson('/api/cart')->assertJsonPath('data.item_count', 5)
            ->assertJsonPath('data.total', '0.50')->assertJsonCount(1, 'data.items');
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_registration_merges_the_cart_and_failed_registration_keeps_it(): void
    {
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 2]);
        $token = session('cart_token');
        $this->postJson('/api/register', ['name' => 'Cliente'])->assertUnprocessable();
        $this->assertSame($token, session('cart_token'));
        $this->postJson('/api/register', [
            'name' => 'Cliente', 'email' => 'novo@example.com', 'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated()->assertJsonPath('data.role', 'cliente')->assertSessionMissing('cart_token');
        $this->assertDatabaseMissing('carts', ['guest_token' => $token]);
        $this->getJson('/api/cart')->assertJsonPath('data.item_count', 2)->assertJsonPath('data.total', '0.20');
    }

    public function test_failed_login_keeps_guest_cart_and_ignores_a_submitted_guest_token(): void
    {
        $user = User::factory()->create();
        $other = Cart::create(['guest_token' => 'another-visitor']);
        Cart_item::create(['cart_id' => $other->id, 'product_id' => $this->product->id, 'quantity' => 5]);
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);
        $token = session('cart_token');
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        $this->assertSame($token, session('cart_token'));
        $this->assertGuest();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password', 'guest_token' => 'another-visitor'])
            ->assertOk();
        $this->getJson('/api/cart')->assertJsonPath('data.item_count', 1);
        $this->assertDatabaseHas('cart_items', ['cart_id' => $other->id, 'quantity' => 5]);
    }

    public function test_cart_survives_logout_and_login_from_a_new_session_without_guest_items(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 2]);
        $this->postJson('/api/logout')->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->getJson('/api/cart')->assertJsonPath('data.item_count', 0);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->getJson('/api/cart')->assertJsonPath('data.item_count', 2);
        $this->assertSame(1, Cart::where('user_id', $user->id)->count());
    }
}
