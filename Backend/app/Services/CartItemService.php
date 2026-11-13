<?php

namespace App\Services;

use App\Models\Cart_item;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartItemService
{
    public function __construct(private CartService $cartService) {}

    public function addToCart(Request $request, int $productId, int $quantity): Cart_item
    {
        return DB::transaction(function () use ($request, $productId, $quantity) {
            $cart = $this->cartService->current($request);
            $product = Product::where('is_active', true)->lockForUpdate()->findOrFail($productId);
            if ($product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Estoque insuficiente para {$product->name}. Disponível: {$product->stock}."],
                ]);
            }
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

    public function updateCurrent(Request $request, int $id, int $quantity): void
    {
        DB::transaction(function () use ($request, $id, $quantity) {
            $cart = $this->cartService->current($request);
            $item = $cart->items()->findOrFail($id);
            if ($quantity === 0) {
                $item->delete();
                return;
            }
            $product = Product::lockForUpdate()->find($item->product_id);
            if (! $product || ! $product->is_active || $quantity > $product->stock) {
                throw ValidationException::withMessages([
                    'quantity' => ['Produto indisponível ou quantidade acima do estoque disponível.'],
                ]);
            }
            $item->update(['quantity' => $quantity]);
        });
    }

    public function listAll(): Collection
    {
        return Cart_item::all();
    }

    public function findById(int $id): Cart_item
    {
        return Cart_item::findOrFail($id);
    }
}
