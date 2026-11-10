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

    public function test_admin_cannot_write_financial_values_directly(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patchJson("/api/orders/{$id}", ['status' => 'pago'])->assertMethodNotAllowed();
        $this->deleteJson("/api/orders/{$id}")->assertMethodNotAllowed();
        $this->postJson('/api/payments', ['order_id' => $id, 'status' => 'pago', 'amount' => '0.01'])->assertUnprocessable();
        $this->patchJson('/api/payments/'.Payment::first()->id, ['amount' => '0.01'])->assertUnprocessable();
        $this->patchJson('/api/order-items/'.Order::first()->items()->first()->id, ['unit_price' => '0.01'])->assertUnprocessable();
    }
    public function test_my_orders_requires_login_and_returns_only_own_orders_in_newest_order(): void
    {
        $this->getJson('/api/my/orders')->assertUnauthorized();
        $this->actingAs($this->user)->getJson('/api/my/orders')->assertOk()->assertJsonCount(0, 'data');
        $this->fillCart(1);
        $first = $this->checkout();
        $this->fillCart(1);
        $second = $this->checkout();
        Order::find($first)->update(['created_at' => now()->subDay()]);
        $this->product->update(['price' => '99.99', 'name' => 'Novo nome']);
        $this->getJson('/api/my/orders?users_id=999')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $second)->assertJsonPath('data.1.id', $first)
            ->assertJsonPath('data.0.total', '20.10');
        $this->getJson("/api/orders/{$first}")->assertOk()->assertJsonPath('data.items.0.product_name', 'Marine');
        $this->actingAs(User::factory()->create())->getJson('/api/my/orders')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/orders')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->getJson('/api/my/orders')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/orders')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_cancellation_returns_all_stock_once_and_keeps_historical_values(): void
    {
        $this->fillCart(2);
        $other = $this->product->replicate();
        $other->save();
        $this->postJson('/api/cart/items', ['product_id' => $other->id, 'quantity' => 1])->assertSuccessful();
        $id = $this->checkout();
        $this->product->update(['is_active' => false, 'price' => '99.99']);
        $other->delete();
        $this->postJson("/api/orders/{$id}/cancel", ['quantity' => 999, 'total' => '0.01', 'status' => 'pago'])
            ->assertOk()->assertJsonPath('data.status', 'cancelado')->assertJsonPath('data.total', '20.30')
            ->assertJsonPath('data.payments.0.status', 'cancelado')->assertJsonPath('data.payments.0.paid_at', null)
            ->assertJsonPath('data.items.0.unit_price', '0.10')->assertJsonCount(2, 'data.items');
        $this->assertSame(5, $this->product->fresh()->stock);
        $this->assertFalse($this->product->fresh()->is_active);
        $this->assertSame(5, Product::withTrashed()->find($other->id)->stock);
        $this->assertTrue(Product::withTrashed()->find($other->id)->trashed());
        $this->postJson("/api/orders/{$id}/cancel")->assertConflict();
        $this->postJson("/api/orders/{$id}/pay")->assertConflict();
        $this->assertSame(5, $this->product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
        $this->getJson("/api/orders/{$id}")->assertOk()->assertJsonPath('data.status', 'cancelado');
    }

    public function test_cancellation_authorization_and_admin_access(): void
    {
        $this->postJson('/api/orders/1/cancel')->assertUnauthorized();
        $this->fillCart();
        $id = $this->checkout();
        $this->actingAs(User::factory()->create())->postJson("/api/orders/{$id}/cancel")->assertForbidden();
        $this->postJson('/api/orders/999999/cancel')->assertNotFound();
        $this->assertSame(2, $this->product->fresh()->stock);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->postJson("/api/orders/{$id}/cancel")->assertOk();
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_cancellation_rejects_non_pending_orders_and_processed_payments(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        foreach (['pago', 'enviado', 'entregue', 'cancelado'] as $status) {
            Order::find($id)->update(['status' => $status]);
            $this->postJson("/api/orders/{$id}/cancel")->assertConflict();
        }
        Order::find($id)->update(['status' => 'pendente']);
        foreach (['pago', 'cancelado'] as $status) {
            Payment::first()->update(['status' => $status]);
            $this->postJson("/api/orders/{$id}/cancel")->assertConflict();
        }
        $this->assertSame(2, $this->product->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'pendente']);
    }

    public function test_cancellation_rolls_back_stock_and_payment_if_order_update_fails(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        Event::listen('eloquent.updating: '.Order::class, fn () => throw new \RuntimeException('Falha simulada'));
        try {
            $this->postJson("/api/orders/{$id}/cancel")->assertServerError();
        } finally {
            Event::forget('eloquent.updating: '.Order::class);
        }
        $this->assertSame(2, $this->product->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'pendente']);
        $this->assertDatabaseHas('payments', ['order_id' => $id, 'status' => 'pendente', 'paid_at' => null]);
    }

    public function test_cancellation_creates_a_cancelled_payment_for_legacy_order_without_one(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        Payment::first()->delete();
        $this->postJson("/api/orders/{$id}/cancel")->assertOk()
            ->assertJsonPath('data.payments.0.status', 'cancelado')->assertJsonPath('data.payments.0.amount', '20.30');
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_own_address_updates_and_deletes_require_session_and_ownership(): void
    {
        $url = "/api/my/addresses/{$this->address->id}";
        $this->patchJson($url, ['street' => 'Rua B'])->assertUnauthorized();
        $this->deleteJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->patchJson($url, ['street' => 'Rua B'])->assertNotFound();
        $this->deleteJson($url)->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->deleteJson($url)->assertNotFound();
        $this->actingAs($this->user)->patchJson($url, ['users_id' => null])->assertUnprocessable()->assertJsonValidationErrors('users_id');
        $this->patchJson($url, ['street' => ''])->assertUnprocessable()->assertJsonValidationErrors('street');
        $this->patchJson($url, ['street' => 'Rua B', 'complement' => null])->assertOk()
            ->assertJsonPath('data.id', $this->address->id)->assertJsonPath('data.street', 'Rua B');
        $this->deleteJson($url)->assertNoContent();
        $this->assertSoftDeleted('addresses', ['id' => $this->address->id]);
        $this->getJson('/api/my/addresses')->assertOk()->assertJsonCount(0, 'data');
        $this->patchJson($url, ['street' => 'Rua C'])->assertNotFound();
        $this->deleteJson($url)->assertNotFound();
    }

    public function test_address_changes_and_deletes_preserve_orders_and_checkout_uses_new_version(): void
    {
        $this->fillCart(1);
        $first = $this->checkout();
        $oldId = $this->address->id;
        $newId = $this->patchJson("/api/my/addresses/{$oldId}", ['street' => 'Rua B'])->assertOk()
            ->assertJsonPath('data.street', 'Rua B')->json('data.id');
        $this->assertNotSame($oldId, $newId);
        $this->assertSoftDeleted('addresses', ['id' => $oldId, 'street' => 'Rua A']);
        $this->getJson("/api/orders/{$first}")->assertOk()->assertJsonPath('data.address.street', 'Rua A');
        $this->getJson('/api/my/addresses')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $newId);
        $this->fillCart(1);
        $this->postJson('/api/orders', ['address_id' => $oldId])->assertNotFound();
        $second = $this->postJson('/api/orders', ['address_id' => $newId])->assertCreated()->json('data.id');
        $this->deleteJson("/api/my/addresses/{$newId}")->assertNoContent();
        $this->getJson("/api/orders/{$second}")->assertOk()->assertJsonPath('data.address.street', 'Rua B');
        $this->getJson('/api/my/addresses')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_address_edits_also_preserve_order_history(): void
    {
        $this->fillCart(1);
        $id = $this->checkout();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $newId = $this->patchJson("/api/addresses/{$this->address->id}", ['street' => 'Rua admin'])
            ->assertOk()->json('data.id');
        $this->deleteJson("/api/addresses/{$newId}")->assertNoContent();
        $this->getJson("/api/orders/{$id}")->assertOk()->assertJsonPath('data.address.street', 'Rua A');
    }

    public function test_address_versioning_rolls_back_if_archiving_fails(): void
    {
        $this->fillCart(1);
        $id = $this->checkout();
        Event::listen('eloquent.deleting: '.Address::class, fn () => throw new \RuntimeException('Falha simulada'));
        try {
            $this->patchJson("/api/my/addresses/{$this->address->id}", ['street' => 'Rua B'])->assertServerError();
        } finally {
            Event::forget('eloquent.deleting: '.Address::class);
        }
        $this->assertDatabaseCount('addresses', 1);
        $this->assertSame('Rua A', $this->address->fresh()->street);
        $this->getJson("/api/orders/{$id}")->assertOk()->assertJsonPath('data.address.street', 'Rua A');
    }

    public function test_order_status_changes_require_admin_even_for_the_owner(): void
    {
        $this->patchJson('/api/admin/orders/1/status', ['status' => 'enviado'])->assertUnauthorized();
        $this->fillCart(1);
        $id = $this->checkout();
        $this->patchJson("/api/admin/orders/{$id}/status", ['status' => 'enviado'])->assertForbidden();
        $this->actingAs(User::factory()->create())->patchJson("/api/admin/orders/{$id}/status", ['status' => 'enviado'])->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'pendente']);
    }

    public function test_admin_can_ship_then_deliver_without_modifying_stock_payment_or_order_values(): void
    {
        $this->fillCart(1);
        $id = $this->checkout();
        $this->postJson("/api/orders/{$id}/pay")->assertOk();
        $payment = Payment::first()->getAttributes();
        $items = Order::find($id)->items()->get()->toArray();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['enviado', 'entregue'] as $status) {
            $this->patchJson("/api/admin/orders/{$id}/status", [
                'status' => $status, 'users_id' => 999, 'address_id' => 999,
                'subtotal' => '0.01', 'total' => '0.01', 'shipping_cost' => '0.00', 'paid_at' => null,
            ])->assertOk()->assertJsonPath('data.status', $status)
                ->assertJsonPath('data.users_id', $this->user->id)->assertJsonPath('data.address.id', $this->address->id)
                ->assertJsonPath('data.total', '20.10')->assertJsonPath('data.subtotal', '0.10')
                ->assertJsonPath('data.shipping_cost', '20.00')->assertJsonPath('data.items.0.product_name', 'Marine')
                ->assertJsonPath('data.payments.0.status', 'pago');
            $this->patchJson("/api/admin/orders/{$id}/status", ['status' => $status])->assertConflict();
        }
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.status', 'entregue');
        $this->assertSame(4, $this->product->fresh()->stock);
        $this->assertSame($payment, Payment::first()->getAttributes());
        $this->assertSame($items, Order::find($id)->items()->get()->toArray());
        $this->actingAs($this->user)->getJson("/api/orders/{$id}")->assertOk()->assertJsonPath('data.status', 'entregue');
        $this->getJson('/api/my/orders')->assertOk()->assertJsonPath('data.0.status', 'entregue');
        $this->postJson("/api/orders/{$id}/cancel")->assertConflict();
        $this->postJson("/api/orders/{$id}/pay")->assertConflict();
    }

    public function test_admin_status_endpoint_rejects_all_other_transitions(): void
    {
        $this->fillCart(1);
        $id = $this->checkout();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $statuses = ['pendente', 'pago', 'enviado', 'entregue', 'cancelado'];
        foreach ($statuses as $from) {
            Order::find($id)->update(['status' => $from]);
            foreach ($statuses as $to) {
                if (($from === 'pago' && $to === 'enviado') || ($from === 'enviado' && $to === 'entregue')) continue;
                $this->patchJson("/api/admin/orders/{$id}/status", ['status' => $to])->assertConflict();
                $this->assertDatabaseHas('orders', ['id' => $id, 'status' => $from]);
            }
        }
        $this->assertSame(4, $this->product->fresh()->stock);
        $this->assertDatabaseHas('payments', ['order_id' => $id, 'status' => 'pendente', 'paid_at' => null]);
    }

    public function test_admin_status_endpoint_validates_target_and_missing_order(): void
    {
        $this->fillCart(1);
        $id = $this->checkout();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patchJson("/api/admin/orders/{$id}/status", [])->assertUnprocessable()->assertJsonValidationErrors('status');
        foreach ([null, '', 'invalido', [], 123] as $invalid) {
            $this->patchJson("/api/admin/orders/{$id}/status", ['status' => $invalid])->assertUnprocessable()->assertJsonValidationErrors('status');
        }
        $this->patchJson('/api/admin/orders/999999/status', ['status' => 'enviado'])->assertNotFound();
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'pendente']);
    }

    public function test_financial_writes_require_an_admin_before_validating_input(): void
    {
        $routes = [
            ['POST', '/api/order-items'], ['PATCH', '/api/order-items/1'],
            ['POST', '/api/payments'], ['PATCH', '/api/payments/1'],
        ];
        foreach ($routes as [$method, $url]) {
            $this->json($method, $url)->assertUnauthorized();
        }
        $this->actingAs($this->user);
        foreach ($routes as [$method, $url]) {
            $this->json($method, $url)->assertForbidden();
        }
    }

    public function test_admin_can_add_items_using_catalog_prices_and_update_stock_order_and_payment(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $other = $this->product->replicate();
        $other->fill(['name' => 'Tinta', 'price' => '0.25', 'stock' => 5])->save();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/order-items', [
            'order_id' => $id, 'product_id' => $other->id, 'quantity' => 2,
            'product_name' => 'Nome falso', 'unit_price' => '0.01', 'subtotal' => '0.01',
        ])->assertCreated()->assertJsonPath('data.product_name', 'Tinta')
            ->assertJsonPath('data.unit_price', '0.25')->assertJsonPath('data.subtotal', '0.50');
        $this->assertSame(3, $other->fresh()->stock);
        $this->assertSame(2, $this->product->fresh()->stock);
        $this->getJson("/api/orders/{$id}")->assertOk()->assertJsonPath('data.subtotal', '0.80')
            ->assertJsonPath('data.shipping_cost', '20.00')->assertJsonPath('data.total', '20.80')
            ->assertJsonPath('data.payments.0.amount', '20.80')->assertJsonPath('data.status', 'pendente');
    }

    public function test_admin_quantity_changes_keep_frozen_prices_and_return_or_reserve_only_the_difference(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $item = Order::findOrFail($id)->items()->firstOrFail();
        $this->product->update(['price' => '99.99', 'name' => 'Novo nome']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patchJson("/api/order-items/{$item->id}", [
            'quantity' => 4, 'order_id' => 999, 'product_id' => 999,
            'product_name' => 'Falso', 'unit_price' => '0.01', 'subtotal' => '0.01',
        ])->assertOk()->assertJsonPath('data.order_id', $id)->assertJsonPath('data.product_id', $this->product->id)
            ->assertJsonPath('data.product_name', 'Marine')->assertJsonPath('data.unit_price', '0.10')
            ->assertJsonPath('data.subtotal', '0.40');
        $this->assertSame(1, $this->product->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $id, 'total' => '20.40']);
        $this->assertDatabaseHas('payments', ['order_id' => $id, 'amount' => '20.40']);
        $this->patchJson("/api/order-items/{$item->id}", ['quantity' => 1])->assertOk()->assertJsonPath('data.subtotal', '0.10');
        $this->assertSame(4, $this->product->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $id, 'total' => '20.10']);
        $this->assertDatabaseHas('payments', ['order_id' => $id, 'amount' => '20.10']);
        foreach ([0, -1, 1.5, 6, 2147483648] as $quantity) {
            $this->patchJson("/api/order-items/{$item->id}", ['quantity' => $quantity])->assertUnprocessable();
        }
        $this->assertSame(4, $this->product->fresh()->stock);
    }

    public function test_unavailable_products_allow_reducing_reserved_items_but_not_increasing_or_adding_them(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $item = Order::findOrFail($id)->items()->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/order-items', ['order_id' => $id, 'product_id' => $this->product->id, 'quantity' => 3])
            ->assertUnprocessable();
        $this->product->update(['is_active' => false]);
        $this->postJson('/api/order-items', ['order_id' => $id, 'product_id' => $this->product->id, 'quantity' => 1])
            ->assertUnprocessable();
        $this->product->delete();
        $this->patchJson("/api/order-items/{$item->id}", ['quantity' => 4])->assertUnprocessable();
        $this->patchJson("/api/order-items/{$item->id}", ['quantity' => 2])->assertOk();
        $this->assertSame(3, Product::withTrashed()->findOrFail($this->product->id)->stock);
        $this->postJson("/api/orders/{$id}/cancel")->assertOk();
        $this->assertSame(5, Product::withTrashed()->findOrFail($this->product->id)->stock);
    }

    public function test_finalized_orders_and_processed_payments_block_item_writes(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $item = Order::findOrFail($id)->items()->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['pago', 'enviado', 'entregue', 'cancelado'] as $status) {
            Order::findOrFail($id)->update(['status' => $status]);
            $this->patchJson("/api/order-items/{$item->id}", ['quantity' => 1])->assertConflict();
            $this->postJson('/api/order-items', ['order_id' => $id, 'product_id' => $this->product->id, 'quantity' => 1])
                ->assertConflict();
        }
        Order::findOrFail($id)->update(['status' => 'pendente']);
        Payment::firstOrFail()->update(['status' => 'pago']);
        $this->patchJson("/api/order-items/{$item->id}", ['quantity' => 1])->assertConflict();
        $this->assertSame(2, $this->product->fresh()->stock);
        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_admin_item_writes_validate_input_and_roll_back_overflow_and_late_failures(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/order-items', [])->assertUnprocessable()->assertJsonValidationErrors(['order_id', 'product_id', 'quantity']);
        $this->patchJson('/api/order-items/999999', ['quantity' => 1])->assertNotFound();
        $other = $this->product->replicate();
        $other->fill(['price' => '99999999.99', 'stock' => 5])->save();
        $this->postJson('/api/order-items', ['order_id' => $id, 'product_id' => $other->id, 'quantity' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $other->update(['price' => '0.25']);
        Event::listen('eloquent.created: '.\App\Models\OrderItem::class, fn () => throw new \RuntimeException('Falha simulada'));
        try {
            $this->postJson('/api/order-items', ['order_id' => $id, 'product_id' => $other->id, 'quantity' => 1])->assertServerError();
        } finally {
            Event::forget('eloquent.created: '.\App\Models\OrderItem::class);
        }
        $this->assertSame(5, $other->fresh()->stock);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('orders', ['id' => $id, 'subtotal' => '0.30', 'total' => '20.30']);
        $this->assertDatabaseHas('payments', ['order_id' => $id, 'status' => 'pendente', 'amount' => '20.30']);
    }

    public function test_admin_can_create_only_one_pending_payment_at_the_order_total(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        Payment::firstOrFail()->delete();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/payments', [])->assertUnprocessable()->assertJsonValidationErrors('order_id');
        $this->postJson('/api/payments', ['order_id' => $id, 'status' => 'pago'])->assertUnprocessable();
        $this->postJson('/api/payments', ['order_id' => $id, 'amount' => '0.01', 'paid_at' => '2000-01-01'])
            ->assertCreated()->assertJsonPath('data.status', 'pendente')->assertJsonPath('data.amount', '20.30')
            ->assertJsonPath('data.paid_at', null);
        $this->postJson('/api/payments', ['order_id' => $id])->assertConflict();
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(2, $this->product->fresh()->stock);
    }

    public function test_admin_payment_confirmation_uses_server_values_and_cannot_be_repeated_or_cancelled(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $payment = Payment::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patchJson("/api/payments/{$payment->id}", [])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->patchJson("/api/payments/{$payment->id}", ['status' => 'invalido'])->assertUnprocessable();
        $this->patchJson("/api/payments/{$payment->id}", ['status' => 'pendente'])->assertConflict();
        $this->patchJson('/api/payments/999999', ['status' => 'pago'])->assertNotFound();
        $this->patchJson("/api/payments/{$payment->id}", [
            'status' => 'pago', 'order_id' => 999, 'amount' => '0.01', 'paid_at' => '2000-01-01',
        ])->assertOk()->assertJsonPath('data.order_id', $id)->assertJsonPath('data.amount', '20.30')
            ->assertJsonPath('data.status', 'pago');
        $paidAt = $payment->fresh()->paid_at;
        $this->assertTrue($paidAt->isToday());
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'pago']);
        foreach (['pago', 'cancelado', 'pendente'] as $status) {
            $this->patchJson("/api/payments/{$payment->id}", ['status' => $status])->assertConflict();
        }
        $this->assertTrue($paidAt->equalTo($payment->fresh()->paid_at));
        $this->assertSame(2, $this->product->fresh()->stock);
    }

    public function test_admin_payment_cancellation_returns_updated_quantities_once_and_rolls_back_on_failure(): void
    {
        $this->fillCart();
        $id = $this->checkout();
        $payment = Payment::firstOrFail();
        $item = Order::findOrFail($id)->items()->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patchJson("/api/order-items/{$item->id}", ['quantity' => 4])->assertOk();
        Event::listen('eloquent.updating: '.Order::class, fn () => throw new \RuntimeException('Falha simulada'));
        try {
            $this->patchJson("/api/payments/{$payment->id}", ['status' => 'cancelado'])->assertServerError();
        } finally {
            Event::forget('eloquent.updating: '.Order::class);
        }
        $this->assertSame(1, $this->product->fresh()->stock);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pendente', 'amount' => '20.40']);
        $this->patchJson("/api/payments/{$payment->id}", ['status' => 'cancelado'])->assertOk()
            ->assertJsonPath('data.status', 'cancelado')->assertJsonPath('data.amount', '20.40');
        $this->assertSame(5, $this->product->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'cancelado', 'total' => '20.40']);
        $this->patchJson("/api/payments/{$payment->id}", ['status' => 'cancelado'])->assertConflict();
        $this->assertSame(5, $this->product->fresh()->stock);
    }

}
