<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function index()
    {
        return CartResource::collection($this->cartService->listAll());
    }

    public function store(StoreCartRequest $request)
    {
        $cart = $this->cartService->create($request->all());
        return response()->json($cart, 201);
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
