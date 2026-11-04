<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
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
