<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\UpdateOrderItemRequest;
use App\Http\Resources\OrderItemResource;
use App\Services\OrderItemService;

class OrderItemController extends Controller
{
    public function __construct(
        private OrderItemService $orderItemService
    ) {}

    public function index()
    {
        return OrderItemResource::collection($this->orderItemService->listAll());
    }

    public function store(StoreOrderItemRequest $request)
    {
        $orderItem = $this->orderItemService->create($request->all());

        return response()->json($orderItem, 201);
    }

    public function show(string $id)
    {
        return new OrderItemResource($this->orderItemService->findById($id));
    }

    public function update(UpdateOrderItemRequest $request, int $id)
    {
        $orderItem = $this->orderItemService->update($id, $request->validated());

        return new OrderItemResource($orderItem);
    }

    public function destroy(string $id)
    {
        $this->orderItemService->delete($id);

        return response()->noContent();
    }
}
