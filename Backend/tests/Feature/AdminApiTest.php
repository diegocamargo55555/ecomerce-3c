<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_limit_is_counted_per_visitor_behind_the_proxy(): void
    {
        $login = fn (string $ip) => $this->withHeader('X-Forwarded-For', $ip)
            ->postJson('/api/login', ['email' => 'ninguem@example.com', 'password' => 'errada']);
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $login('203.0.113.1')->assertUnprocessable();
        }
        $login('203.0.113.1')->assertTooManyRequests();
        $login('203.0.113.2')->assertUnprocessable();
    }

    public function test_user_with_a_cart_is_deleted_and_orders_or_last_admin_block_it(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin);

        $withCart = User::factory()->create();
        Cart::create(['user_id' => $withCart->id]);
        $this->deleteJson("/api/users/{$withCart->id}")->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $withCart->id]);
        $this->assertDatabaseCount('carts', 0);

        $buyer = User::factory()->create();
        $address = Address::create([
            'users_id' => $buyer->id, 'recipient_name' => 'Ana', 'zip_code' => '01000-000', 'street' => 'Rua A',
            'number' => '1', 'neighborhood' => 'Centro', 'city' => 'São Paulo', 'state' => 'SP',
        ]);
        Order::create([
            'users_id' => $buyer->id, 'address_id' => $address->id, 'status' => 'pendente',
            'subtotal' => '10.00', 'shipping_cost' => '20.00', 'total' => '30.00',
        ]);
        $this->deleteJson("/api/users/{$buyer->id}")->assertConflict();
        $this->assertDatabaseHas('users', ['id' => $buyer->id]);

        $this->deleteJson("/api/users/{$admin->id}")->assertConflict();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_opening_the_shop_as_a_guest_does_not_store_a_cart(): void
    {
        $this->withHeader('Origin', 'http://localhost'); // sessão, como no navegador
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total', '0.00')->assertJsonPath('data.item_count', 0);
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_created_records_use_the_same_shape_as_the_other_endpoints(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin);

        $categoryId = $this->postJson('/api/categories', ['name' => 'Tintas', 'slug' => 'tintas', 'ignored' => 'x'])
            ->assertCreated()->assertJsonPath('data.slug', 'tintas')->json('data.id');
        $this->assertNotNull(Category::find($categoryId));
        $this->postJson('/api/products', [
            'category_id' => $categoryId, 'name' => 'Azul', 'description' => 'Tinta azul', 'price' => '9.90',
        ])->assertCreated()->assertJsonPath('data.price', '9.90')
            ->assertJsonPath('data.stock', 0)->assertJsonPath('data.is_active', true);
        $this->postJson('/api/users', ['name' => 'Bia', 'email' => 'bia@example.com', 'password' => 'password123'])
            ->assertCreated()->assertJsonPath('data.role', 'cliente')->assertJsonMissingPath('data.password');
    }
}
