<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function current(Request $request)
    {
        $cart = $request->user() || $request->session()->has('cart_token')
            ? $this->cartService->current($request) : new Cart;

        return (new CartResource($this->cartService->details($cart)))
            ->response()->setStatusCode(200);
    }

    public function index()
    {
        return CartResource::collection($this->cartService->listAll());
    }

    public function show(string $id)
    {
        return new CartResource($this->cartService->findById($id));
    }
}
