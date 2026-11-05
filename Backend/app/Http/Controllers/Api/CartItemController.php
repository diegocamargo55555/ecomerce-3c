<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Models\Cart_item;
use App\Services\ShopService;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    private function owned(Request $request)
    {
        return Cart_item::whereHas('cart', fn ($query) => $query->where('user_id', $request->user()->id));
    }

    public function index(Request $request)
    {
        return CartItemResource::collection($this->owned($request)->with('product')->get());
    }

    public function store(StoreCartItemRequest $request, ShopService $shop)
    {
        return (new CartItemResource($shop->saveItem($request->user(), $request->validated())))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id)
    {
        return new CartItemResource($this->owned($request)->with('product')->findOrFail($id));
    }

    public function update(Request $request, int $id, ShopService $shop)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:2147483647']]);
        return new CartItemResource($shop->saveItem($request->user(), $data, $id));
    }

    public function destroy(Request $request, int $id, ShopService $shop)
    {
        $shop->removeItem($request->user(), $id);
        return response()->noContent();
    }
}
