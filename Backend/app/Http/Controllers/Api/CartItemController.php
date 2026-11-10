<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Services\CartItemService;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function __construct(
        private CartItemService $cartItemService,
        private CartService $cartService,
    ) {}

    public function index()
    {
        return CartItemResource::collection($this->cartItemService->listAll());
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ]);
        return new CartItemResource($this->cartItemService->addToCart(
            $request, $data['product_id'], $data['quantity'],
        ));
    }

    public function updateCurrent(Request $request, int $id)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:4294967295']]);
        $this->cartItemService->updateCurrent($request, $id, $data['quantity']);

        return new CartResource($this->cartService->details($this->cartService->current($request)));
    }

    public function removeCurrent(Request $request, int $id)
    {
        $this->cartItemService->updateCurrent($request, $id, 0);

        return new CartResource($this->cartService->details($this->cartService->current($request)));
    }

    public function store(StoreCartItemRequest $request)
    {
        return new CartItemResource($this->cartItemService->create($request->validated()));
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
