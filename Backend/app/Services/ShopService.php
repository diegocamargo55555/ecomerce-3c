<?php

namespace App\Services;

use App\Models\{Cart, Cart_item, Order, Product, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShopService
{
    public function currentCart(User $user): Cart
    {
        return DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            return $user->carts()->orderBy('id')->firstOrCreate([]);
        });
    }

    public function saveItem(User $user, array $data, ?int $itemId = null): Cart_item
    {
        return DB::transaction(function () use ($user, $data, $itemId) {
            $existing = $itemId ? Cart_item::whereHas('cart', fn ($q) => $q->where('user_id', $user->id))->findOrFail($itemId) : null;
            $cartId = $existing?->cart_id ?? $data['cart_id'];
            $cart = $user->carts()->whereKey($cartId)->lockForUpdate()->firstOrFail();
            if ($existing) {
                $existing = $cart->items()->lockForUpdate()->findOrFail($itemId);
            }
            $product = Product::whereKey($existing?->product_id ?? $data['product_id'])->lockForUpdate()->firstOrFail();
            $item = $existing ?? $cart->items()->where('product_id', $product->id)->lockForUpdate()->first();
            $quantity = $itemId ? $data['quantity'] : ($item?->quantity ?? 0) + $data['quantity'];
            if (! $product->is_active || $quantity > $product->stock) {
                throw ValidationException::withMessages(['quantity' => 'Quantidade indisponível em estoque.']);
            }
            if (! $item) {
                $item = $cart->items()->make(['product_id' => $product->id]);
            }
            $item->quantity = $quantity;
            $item->save();
            return $item->load('product');
        });
    }

    public function removeItem(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id) {
            $item = Cart_item::whereHas('cart', fn ($q) => $q->where('user_id', $user->id))->findOrFail($id);
            $user->carts()->whereKey($item->cart_id)->lockForUpdate()->firstOrFail();
            $item->delete();
        });
    }

    public function checkout(User $user, int $addressId): Order
    {
        return DB::transaction(function () use ($user, $addressId) {
            $address = $user->addresses()->findOrFail($addressId);
            $cart = $user->carts()->orderBy('id')->lockForUpdate()->first();
            $items = $cart?->items()->orderBy('product_id')->lockForUpdate()->get();
            if (! $items || $items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Seu carrinho está vazio.']);
            }
            $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $totalCents = 0;
            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                if (! $product || ! $product->is_active || $item->quantity > $product->stock) {
                    throw ValidationException::withMessages(['cart' => 'Um produto está indisponível. Revise seu carrinho.']);
                }
                $totalCents += (int) round((float) $product->price * 100) * $item->quantity;
            }
            if ($totalCents > 9999999999) {
                throw ValidationException::withMessages(['cart' => 'O valor do pedido excede o limite permitido.']);
            }
            $order = $user->orders()->create([
                'address_id' => $address->id, 'status' => 'pendente',
                'subtotal' => $totalCents / 100, 'total' => $totalCents / 100, 'shipping_cost' => 0,
            ]);
            foreach ($items as $item) {
                $product = $products[$item->product_id];
                $order->items()->create([
                    'product_id' => $product->id, 'product_name' => $product->name,
                    'unit_price' => $product->price, 'quantity' => $item->quantity,
                    'subtotal' => ((int) round((float) $product->price * 100) * $item->quantity) / 100,
                ]);
                $product->decrement('stock', $item->quantity);
            }
            $order->stock_deducted = true;
            $order->save();
            $cart->items()->delete();
            return $order->load(['items', 'address', 'user']);
        });
    }

    public function transitionOrder(int $id, string $status): Order
    {
        return DB::transaction(function () use ($id, $status) {
            $order = Order::lockForUpdate()->findOrFail($id);
            if ($order->status !== 'pendente') {
                throw ValidationException::withMessages(['status' => 'Este pedido já foi finalizado e não pode ser alterado.']);
            }
            if ($status === 'cancelado' && $order->stock_deducted) {
                foreach ($order->items()->orderBy('product_id')->get() as $item) {
                    $product = Product::withTrashed()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                    $product->increment('stock', $item->quantity);
                }
                $order->stock_deducted = false;
            }
            $order->status = $status;
            $order->save();
            return $order->load(['items', 'address', 'user']);
        });
    }
}
