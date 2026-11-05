<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Services\ShopService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function current(Request $request, ShopService $shop)
    {
        return (new CartResource($shop->currentCart($request->user())->load('items.product')))
            ->response()->setStatusCode(200);
    }

    public function index(Request $request)
    {
        return CartResource::collection($request->user()->carts()->with('items.product')->get());
    }

    public function store(Request $request, ShopService $shop)
    {
        return $this->current($request, $shop);
    }

    public function show(Request $request, int $id)
    {
        return new CartResource($request->user()->carts()->with('items.product')->findOrFail($id));
    }
}
