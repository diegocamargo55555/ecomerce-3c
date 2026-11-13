<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const SHIPPING_COST = '20.00';
    private const FREE_SHIPPING_ABOVE = '100.00';

    public function __construct(private CartService $cartService) {}

    public function review(Request $request): array
    {
        $cart = $this->cartService->current($request);
        $cart->load('items.product');

        return $this->quote($cart);
    }

    private function quote(Cart $cart): array
    {
        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => ['Seu carrinho está vazio.']]);
        }
        $subtotal = '0.00';
        $items = [];
        foreach ($cart->items as $item) {
            $product = $item->product;
            if (! $product || ! $product->is_active|| $item->quantity < 1 || $item->quantity > $product->stock) {
                $name = $product?->name ?? "Produto #{$item->product_id}";
                throw ValidationException::withMessages(['cart' => ["{$name}: produto indisponível ou estoque insuficiente."]]);
            }
            $lineTotal = bcmul($product->price, (string) $item->quantity, 2);
            $subtotal = bcadd($subtotal, $lineTotal, 2);
            $items[] = [
                'product_id' => $product->id, 'product_name' => $product->name,
                'unit_price' => $product->price, 'quantity' => $item->quantity, 'subtotal' => $lineTotal,
            ];
        }
        $shipping = bccomp($subtotal, self::FREE_SHIPPING_ABOVE, 2) > 0 ? '0.00' : self::SHIPPING_COST;
        $total = bcadd($subtotal, $shipping, 2);
        if (bccomp($total, '99999999.99', 2) > 0) {
            throw ValidationException::withMessages(['cart' => ['O total ultrapassa o limite de R$ 99.999.999,99 por pedido.']]);
        }

        return ['items' => $items, 'subtotal' => $subtotal, 'shipping_cost' => $shipping, 'total' => $total];
    }

    public function checkout(Request $request, int $addressId): Order
    {
        return DB::transaction(function () use ($request, $addressId) {
            $cart = $this->cartService->current($request);
            $address = Address::where('users_id', $request->user()->id)->lockForUpdate()->findOrFail($addressId);
            $items = $cart->items()->orderBy('product_id')->lockForUpdate()->get();
            $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($items as $item) {
                $item->setRelation('product', $products->get($item->product_id));
            }
            $cart->setRelation('items', $items);
            $quote = $this->quote($cart);
            $order = Order::create([
                'users_id' => $request->user()->id, 'address_id' => $address->id, 'status' => 'pendente',
                'subtotal' => $quote['subtotal'], 'shipping_cost' => $quote['shipping_cost'], 'total' => $quote['total'],
            ]);
            $order->items()->createMany($quote['items']);
            foreach ($items as $item) {
                $item->product->decrement('stock', $item->quantity);
            }
            $order->payments()->create(['status' => 'pendente', 'amount' => $order->total]);
            $cart->items()->delete();

            return $order->load('items', 'address', 'payments');
        }, 3);
    }

    public function findForUser(int $id, User $user): Order
    {
        $order = Order::findOrFail($id);
        abort_unless($order->users_id === $user->id || $user->role === 'admin', 403);

        return $order->load('items', 'address', 'payments');
    }

    public function listForUser(User $user): Collection
    {
        return Order::where('users_id', $user->id)->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    public function cancel(int $id, User $user): Order
    {
        return DB::transaction(function () use ($id, $user) {
            $order = Order::lockForUpdate()->findOrFail($id);
            abort_unless($order->users_id === $user->id || $user->role === 'admin', 403);
            abort_unless($order->status === 'pendente', 409, 'Este pedido não está aguardando pagamento.');
            $payment = Payment::where('order_id', $id)->lockForUpdate()->firstOrNew();
            abort_if($payment->exists && $payment->status !== 'pendente', 409, 'O pagamento deste pedido já foi processado.');
            $items = $order->items()->orderBy('product_id')->lockForUpdate()->get();
            $products = Product::whereIn('id', $items->pluck('product_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($items as $item) {
                $products->get($item->product_id)->increment('stock', $item->quantity);
            }
            $payment->fill(['order_id' => $id, 'amount' => $order->total, 'status' => 'cancelado', 'paid_at' => null])->save();
            $order->update(['status' => 'cancelado']);

            return $order->load('items', 'address', 'payments');
        }, 3);
    }

    public function updateStatus(int $id, string $status): Order
    {
        return DB::transaction(function () use ($id, $status) {
            $order = Order::lockForUpdate()->findOrFail($id);
            $previousStatus = ['enviado' => 'pago', 'entregue' => 'enviado'];
            abort_unless($order->status === ($previousStatus[$status] ?? null), 409, 'Transição de status inválida para este pedido.');
            $order->update(['status' => $status]);

            return $order->load('items', 'address', 'payments');
        }, 3);
    }

    public function listAll(): Collection
    {
        return Order::all();
    }

}
