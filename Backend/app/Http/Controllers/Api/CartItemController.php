<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Requests\UpdateCurrentCartItemRequest;
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

    public function add(AddCartItemRequest $request)
    {
        return new CartItemResource($this->cartItemService->addToCart(
            $request, $request->validated('product_id'), $request->validated('quantity'),
        ));
    }

    public function updateCurrent(UpdateCurrentCartItemRequest $request, int $id)
    {
        $this->cartItemService->updateCurrent($request, $id, $request->validated('quantity'));

        return new CartResource($this->cartService->details($this->cartService->current($request)));
    }

    public function removeCurrent(Request $request, int $id)
    {
        $this->cartItemService->updateCurrent($request, $id, 0);

        return new CartResource($this->cartService->details($this->cartService->current($request)));
    }

    public function store(StoreCartItemRequest $request)
    {
        $cartItem = $this->cartItemService->create($request->validated());

        return (new CartItemResource($cartItem))->response()->setStatusCode(201);
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
