<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderItemService;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function __construct(
        private OrderItemService $orderItemService
    ) {}

    public function index()
    {
        return response()->json($this->orderItemService->listAll());
    }

    public function store(Request $request)
    {
        $orderItem = $this->orderItemService->create($request->all());

        return response()->json($orderItem, 201);
    }

    public function show(string $id)
    {
        return response()->json($this->orderItemService->findById($id));
    }

    public function update(Request $request, string $id)
    {
        $orderItem = $this->orderItemService->update($id, $request->all());

        return response()->json($orderItem);
    }

    public function destroy(string $id)
    {
        $this->orderItemService->delete($id);

        return response()->noContent();
    }
}
