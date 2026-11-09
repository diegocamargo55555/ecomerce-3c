<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

}
