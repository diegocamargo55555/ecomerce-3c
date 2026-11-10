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

    public function show(string $id)
    {
        return new OrderItemResource($this->orderItemService->findById($id));
    }

    public function store(StoreOrderItemRequest $request)
    {
        return (new OrderItemResource($this->orderItemService->create($request->validated())))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateOrderItemRequest $request, int $id)
    {
        return new OrderItemResource($this->orderItemService->update($id, $request->validated('quantity')));
    }

}
