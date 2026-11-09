<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Services\CartItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartItemController extends Controller
{
    public function __construct(
        private CartItemService $cartItemService
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
        $userId = $request->user()?->id;
        $guestToken = null;
        if ($userId === null) {
            $guestToken = $request->session()->get('cart_token') ?? Str::random(64);
            $request->session()->put('cart_token', $guestToken);
        }

        return new CartItemResource($this->cartItemService->addToCart(
            $data['product_id'], $data['quantity'], $userId, $guestToken,
        ));
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
