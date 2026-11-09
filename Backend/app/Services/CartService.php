<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Cart_item;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public function current(Request $request): Cart
    {
        return DB::transaction(function () use ($request) {
            if ($userId = $request->user()?->id) {
                User::whereKey($userId)->lockForUpdate()->firstOrFail();
                $identity = ['user_id' => $userId];
            } else {
                $token = $request->session()->get('cart_token') ?? Str::random(64);
                $request->session()->put('cart_token', $token);
                $identity = ['user_id' => null, 'guest_token' => $token];
            }

            return Cart::lockForUpdate()->firstOrCreate($identity);
        });
    }

    public function details(Cart $cart): Cart
    {
        $cart->load(['items.product' => fn ($query) => $query->withTrashed()]);
        $total = '0.00';
        foreach ($cart->items as $item) {
            $product = $item->product;
            $item->subtotal = bcmul($product->price, (string) $item->quantity, 2);
            $item->unavailable_reason = match (true) {
                $product->trashed() || ! $product->is_active => 'Produto indisponível.',
                $product->stock === 0 => 'Produto sem estoque.',
                $item->quantity > $product->stock => "Estoque disponível: {$product->stock} unidade(s). Ajuste a quantidade.",
                default => null,
            };
            $item->available = $item->unavailable_reason === null;
            $total = bcadd($total, $item->subtotal, 2);
        }
        $cart->total = $total;
        $cart->item_count = $cart->items->sum('quantity');

        return $cart;
    }

    public function merge(User $user, ?string $guestToken): array
    {
        if ($guestToken === null) {
            return [];
        }

        return DB::transaction(function () use ($user, $guestToken) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $cart = Cart::lockForUpdate()->firstOrCreate(['user_id' => $user->id]);
            $guest = Cart::whereNull('user_id')->where('guest_token', $guestToken)->lockForUpdate()->first();
            if (! $guest) {
                return [];
            }

            $items = Cart_item::whereIn('cart_id', [$cart->id, $guest->id])->get()->groupBy('product_id');
            $products = Product::withTrashed()->whereIn('id', $items->keys())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $warnings = [];
            foreach ($items as $productId => $lines) {
                $product = $products[$productId];
                $requested = $lines->sum('quantity');
                $quantity = $product->is_active && ! $product->trashed()
                    ? min($requested, $product->stock) : 0;
                if ($quantity === 0) {
                    $cart->items()->where('product_id', $productId)->delete();
                    $warnings[] = "{$product->name} foi removido do carrinho por estar indisponível.";
                    continue;
                }
                $cart->items()->updateOrCreate(['product_id' => $productId], ['quantity' => $quantity]);
                if ($quantity < $requested) {
                    $warnings[] = "{$product->name}: quantidade ajustada de {$requested} para {$quantity} por limite de estoque.";
                }
            }
            $guest->delete();

            return $warnings;
        });
    }

    public function listAll(): Collection
    {
        return Cart::all();
    }

    public function create(array $data): Cart
    {
        return Cart::create($data);
    }

    public function findById(int $id): Cart
    {
        return Cart::findOrFail($id);
    }

    public function update(int $id, array $data): Cart
    {
        $cart = Cart::findOrFail($id);
        $cart->update($data);

        return $cart;
    }

    public function delete(int $id): void
    {
        Cart::findOrFail($id)->delete();
    }
}
