<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index()
    {
        return OrderResource::collection($this->orderService->listAll());
    }

    public function review(Request $request)
    {
        return response()->json(['data' => $this->orderService->review($request)]);
    }

    public function mine(Request $request)
    {
        return OrderResource::collection($this->orderService->listForUser($request->user()));
    }

    public function cancel(Request $request, int $id)
    {
        return new OrderResource($this->orderService->cancel($id, $request->user()));
    }

    public function store(StoreOrderRequest $request)
    {
        return (new OrderResource($this->orderService->checkout($request, $request->validated('address_id'))))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id)
    {
        return new OrderResource($this->orderService->findForUser($id, $request->user()));
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['required', 'string', 'in:pendente,pago,enviado,entregue,cancelado']]);

        return new OrderResource($this->orderService->updateStatus($id, $data['status']));
    }
}
