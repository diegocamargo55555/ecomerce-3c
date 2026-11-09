<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart_item;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('Origin', 'http://localhost');
        $this->user = User::factory()->create();
        $category = Category::create(['name' => 'Miniaturas', 'slug' => 'miniaturas']);
        $this->product = Product::create([
            'category_id' => $category->id, 'name' => 'Marine', 'description' => 'Para pintar',
            'price' => '0.10', 'stock' => 5, 'is_active' => true,
        ]);
        $this->address = Address::create(['users_id' => $this->user->id, ...$this->addressData()]);
    }

    private function addressData(): array
    {
        return [
            'recipient_name' => 'Cliente', 'zip_code' => '01001000', 'street' => 'Rua A', 'number' => '10',
            'complement' => null, 'neighborhood' => 'Centro', 'city' => 'São Paulo', 'state' => 'SP',
        ];
    }

    private function fillCart(int $quantity = 3): void
    {
        $this->actingAs($this->user)->postJson('/api/cart/items', [
            'product_id' => $this->product->id, 'quantity' => $quantity,
        ])->assertSuccessful();
    }

    private function checkout(): int
    {
        return $this->postJson('/api/orders', ['address_id' => $this->address->id])
            ->assertCreated()->json('data.id');
    }

    public function test_checkout_addresses_order_details_and_payment_require_authentication(): void
    {
        $this->getJson('/api/checkout')->assertUnauthorized();
        $this->postJson('/api/orders', ['address_id' => $this->address->id])->assertUnauthorized();
        $this->getJson('/api/orders/1')->assertUnauthorized();
        $this->postJson('/api/orders/1/pay')->assertUnauthorized();
        $this->getJson('/api/my/addresses')->assertUnauthorized();
        $this->postJson('/api/my/addresses', $this->addressData())->assertUnauthorized();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_client_can_list_and_create_only_own_addresses(): void
    {
        $other = User::factory()->create();
        Address::create(['users_id' => $other->id, ...$this->addressData()]);
        $this->actingAs($this->user)->getJson('/api/my/addresses')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $this->address->id);
        $id = $this->postJson('/api/my/addresses', $this->addressData())->assertCreated()->json('data.id');
        $this->assertDatabaseHas('addresses', ['id' => $id, 'users_id' => $this->user->id]);
        $this->postJson('/api/my/addresses', ['users_id' => $other->id, ...$this->addressData()])
            ->assertUnprocessable()->assertJsonValidationErrors('users_id');
    }

    public function test_review_and_checkout_use_database_prices_and_freeze_the_order(): void
    {
        $this->fillCart();
        $this->getJson('/api/checkout')->assertOk()->assertJsonPath('data.subtotal', '0.30')
            ->assertJsonPath('data.shipping_cost', '20.00')->assertJsonPath('data.total', '20.30');
        $this->assertSame(5, $this->product->fresh()->stock);
        $this->product->update(['price' => '0.20']);
        $id = $this->postJson('/api/orders', [
            'address_id' => $this->address->id, 'users_id' => User::factory()->create()->id,
            'status' => 'pago', 'subtotal' => '0.01', 'shipping_cost' => '0.00', 'total' => '0.01',
        ])->assertCreated()->assertJsonPath('data.users_id', $this->user->id)
            ->assertJsonPath('data.status', 'pendente')->assertJsonPath('data.total', '20.60')
            ->assertJsonPath('data.items.0.unit_price', '0.20')->assertJsonPath('data.items.0.subtotal', '0.60')
            ->assertJsonPath('data.payments.0.status', 'pendente')->assertJsonPath('data.payments.0.amount', '20.60')
            ->assertJsonPath('data.payments.0.paid_at', null)->json('data.id');
        $this->assertSame(2, $this->product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
        $this->product->update(['name' => 'Novo nome', 'price' => '99.99']);
        $this->product->delete();
        $this->getJson("/api/orders/{$id}")->assertOk()->assertJsonPath('data.items.0.product_name', 'Marine')
            ->assertJsonPath('data.items.0.unit_price', '0.20')->assertJsonPath('data.total', '20.60');
    }

    public function test_empty_and_repeated_checkout_do_not_create_orders_or_decrement_stock_twice(): void
    {
        $this->actingAs($this->user)->getJson('/api/checkout')->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->postJson('/api/orders', ['address_id' => $this->address->id])->assertUnprocessable();
        $this->fillCart();
        $this->checkout();
        $this->postJson('/api/orders', ['address_id' => $this->address->id])->assertUnprocessable();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(2, $this->product->fresh()->stock);
    }

    public function test_foreign_address_and_missing_address_are_rejected_without_touching_the_cart(): void
    {
        $this->fillCart();
        $other = Address::create(['users_id' => User::factory()->create()->id, ...$this->addressData()]);
        $this->postJson('/api/orders', ['address_id' => $other->id])->assertNotFound();
        $this->postJson('/api/orders', [])->assertUnprocessable()->assertJsonValidationErrors('address_id');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_one_invalid_product_blocks_the_whole_order_and_names_the_failed_product(): void
    {
        $this->fillCart();
        $other = $this->product->replicate();
        $other->name = 'Produto indisponível';
        $other->save();
        $this->postJson('/api/cart/items', ['product_id' => $other->id, 'quantity' => 1]);
        foreach ([['stock' => 0], ['stock' => 5, 'is_active' => false]] as $change) {
            $other->update($change);
            $response = $this->postJson('/api/orders', ['address_id' => $this->address->id])
                ->assertUnprocessable()->assertJsonValidationErrors('cart');
            $this->assertStringContainsString($other->name, $response->json('errors.cart.0'));
        }
        $other->delete();
        $this->postJson('/api/orders', ['address_id' => $this->address->id])->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('cart_items', 2);
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_insufficient_stock_and_decimal_overflow_are_validation_errors(): void
    {
        $this->fillCart();
        $this->product->update(['stock' => 2]);
        $this->postJson('/api/orders', ['address_id' => $this->address->id])->assertUnprocessable();
        $this->product->update(['stock' => 5, 'price' => '99999999.99']);
        $this->postJson('/api/orders', ['address_id' => $this->address->id])->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_checkout_rolls_back_items_stock_and_cart_if_payment_creation_fails(): void
    {
        $this->fillCart();
        Event::listen('eloquent.creating: '.Payment::class, fn () => throw new \RuntimeException('Falha simulada'));
        try {
            $this->postJson('/api/orders', ['address_id' => $this->address->id])->assertServerError();
        } finally {
            Event::forget('eloquent.creating: '.Payment::class);
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_owner_can_pay_once_without_changing_stock_or_accepting_a_forged_amount(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $response = $this->postJson("/api/orders/{$id}/pay", ['amount' => '0.01', 'paid_at' => '2000-01-01'])
            ->assertOk()->assertJsonPath('data.status', 'pago')->assertJsonPath('data.payments.0.status', 'pago')
            ->assertJsonPath('data.payments.0.amount', '20.30');
        $this->assertNotNull($response->json('data.payments.0.paid_at'));
        $paidAt = Payment::first()->paid_at;
        $this->postJson("/api/orders/{$id}/pay")->assertConflict();
        $this->assertTrue($paidAt->equalTo(Payment::first()->paid_at));
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(2, $this->product->fresh()->stock);
        foreach (['enviado', 'entregue', 'cancelado'] as $status) {
            Order::findOrFail($id)->update(['status' => $status]);
            $this->postJson("/api/orders/{$id}/pay")->assertConflict();
        }
    }

    public function test_another_customer_cannot_view_or_pay_and_admin_can_only_view_another_order(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $this->actingAs(User::factory()->create())->getJson("/api/orders/{$id}")->assertForbidden();
        $this->postJson("/api/orders/{$id}/pay")->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->getJson("/api/orders/{$id}")->assertOk();
        $this->postJson("/api/orders/{$id}/pay")->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'pendente']);
        $this->assertDatabaseHas('payments', ['order_id' => $id, 'status' => 'pendente', 'paid_at' => null]);
    }

    public function test_payment_rolls_back_if_the_order_status_cannot_be_saved(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        Event::listen('eloquent.updating: '.Order::class, fn () => throw new \RuntimeException('Falha simulada'));
        try {
            $this->postJson("/api/orders/{$id}/pay")->assertServerError();
        } finally {
            Event::forget('eloquent.updating: '.Order::class);
        }
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'pendente']);
        $this->assertDatabaseHas('payments', ['order_id' => $id, 'status' => 'pendente', 'paid_at' => null]);
    }

    public function test_admin_cannot_bypass_checkout_or_payment_by_editing_financial_records(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patchJson("/api/orders/{$id}", ['status' => 'pago'])->assertMethodNotAllowed();
        $this->deleteJson("/api/orders/{$id}")->assertMethodNotAllowed();
        $this->postJson('/api/payments', ['order_id' => $id, 'status' => 'pago', 'amount' => '0.01'])->assertMethodNotAllowed();
        $this->patchJson('/api/payments/'.Payment::first()->id, ['status' => 'pago'])->assertMethodNotAllowed();
        $this->patchJson('/api/order-items/'.Order::first()->items()->first()->id, ['unit_price' => '0.01'])->assertMethodNotAllowed();
    }
}
