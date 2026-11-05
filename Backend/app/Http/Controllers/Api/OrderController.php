<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\ShopService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private function visible(Request $request)
    {
        return Order::query()->when(! $request->routeIs('admin.*'), fn ($q) => $q->where('users_id', $request->user()->id));
    }

    public function index(Request $request)
    {
        return OrderResource::collection($this->visible($request)->with(['items', 'address', 'user'])->latest('id')->get());
    }

    public function show(Request $request, int $id)
    {
        return new OrderResource($this->visible($request)->with(['items', 'address', 'user'])->findOrFail($id));
    }

    public function update(Request $request, int $id, ShopService $shop)
    {
        $data = $request->validate(['status' => ['required', 'in:pago,cancelado']]);
        return new OrderResource($shop->transitionOrder($id, $data['status']));
    }
}
