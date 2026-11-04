<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Services\CartItemService;

class CartItemController extends Controller
{
    public function __construct(
        private CartItemService $cartItemService
    ) {}

    public function index()
    {
        return CartItemResource::collection($this->cartItemService->listAll());
    }

    public function store(StoreCartItemRequest $request)
    {
        $cartItem = $this->cartItemService->create($request->all());

        return response()->json($cartItem, 201);
    }

    public function show(string $id)
    {
        return new CartItemResource($this->cartItemService->findById($id));
    }

    public function update(UpdateCartItemRequest $request, int $id)
    {
        $cartItem = $this->cartItemService->update($id, $request->validated());

        return new CartItemResource($cartItem);
    }

    public function destroy(string $id)
    {
        $this->cartItemService->delete($id);

        return response()->noContent();
    }
}
