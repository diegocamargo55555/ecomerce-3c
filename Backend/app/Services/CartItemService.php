<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Cart_item;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartItemService
{
    public function addToCart(int $productId, int $quantity, ?int $userId, ?string $guestToken): Cart_item
    {
        return DB::transaction(function () use ($productId, $quantity, $userId, $guestToken) {
            $product = Product::where('is_active', true)->lockForUpdate()->findOrFail($productId);
            if ($product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Estoque insuficiente para {$product->name}. Disponível: {$product->stock}."],
                ]);
            }
            if ($userId !== null) {
                User::whereKey($userId)->lockForUpdate()->firstOrFail();
            }
            $cart = Cart::firstOrCreate($userId !== null
                ? ['user_id' => $userId]
                : ['guest_token' => $guestToken, 'user_id' => null]);
            $item = Cart_item::firstOrNew(['cart_id' => $cart->id, 'product_id' => $productId]);
            $total = ($item->quantity ?? 0) + $quantity;
            if ($total > $product->stock) {
                throw ValidationException::withMessages([
                    'quantity' => ["Você já tem {$item->quantity} unidade(s) no carrinho. Estoque disponível: {$product->stock}."],
                ]);
            }
            $item->quantity = $total;
            $item->save();

            return $item;
        });
    }

    public function listAll(): Collection
    {
        return Cart_item::all();
    }

    public function create(array $data): Cart_item
    {
        return Cart_item::create($data);
    }

    public function findById(int $id): Cart_item
    {
        return Cart_item::findOrFail($id);
    }

    public function update(int $id, array $data): Cart_item
    {
        $cartItem = Cart_item::findOrFail($id);
        $cartItem->update($data);

        return $cartItem;
    }

    public function delete(int $id): void
    {
        Cart_item::findOrFail($id)->delete();
    }
}
