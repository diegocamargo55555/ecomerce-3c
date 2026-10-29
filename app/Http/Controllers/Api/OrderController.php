<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
    
    
    public function index()
    {
        
        return response()->json($this->orderService->listAll());

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $order = $this->orderService->create($request->all());
        return response()->json($order, 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
                
        return response()->json($this->OrderService->findById($id));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        $order->update($request->all());

        return response()->json($order);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Order::findOrFail($id)->delete();
        return response()->noContent();
    }
}
