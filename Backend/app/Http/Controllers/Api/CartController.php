<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
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

    public function store(StoreCartRequest $request)
    {
        $cart = $this->cartService->create($request->validated());
        return (new CartResource($cart))->response()->setStatusCode(201);
    }

    public function show(string $id)
    {
        return new CartResource($this->cartService->findById($id));
    }

    public function update(UpdateCartRequest $request, int $id)
    {
        $cart = $this->cartService->update($id, $request->validated());
        return new CartResource($cart);
    }

    public function destroy(string $id)
    {
        $this->cartService->delete($id);

        return response()->noContent();
    }
}
