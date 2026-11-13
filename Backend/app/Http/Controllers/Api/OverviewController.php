<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;

class OverviewController extends Controller
{
    /**
     * Números do painel calculados no banco, para a visão geral não baixar
     * as tabelas inteiras de produtos, pedidos, pagamentos e usuários.
     */
    public function __invoke()
    {
        $lowStock = Product::where('is_active', true)->where('stock', '<=', 5);

        return response()->json(['data' => [
            'orders_count' => Order::count(),
            'products_count' => Product::count(),
            'active_products_count' => Product::where('is_active', true)->count(),
            'paid_total' => bcadd('0', (string) Payment::where('status', 'pago')->sum('amount'), 2),
            'low_stock_count' => $lowStock->count(),
            'low_stock' => ProductResource::collection($lowStock->orderBy('stock')->orderBy('id')->limit(5)->get()),
            'recent_orders' => Order::with('user:id,name')->orderByDesc('id')->limit(5)->get()
                ->map(fn (Order $order) => [
                    'id' => $order->id, 'status' => $order->status, 'total' => $order->total,
                    'created_at' => $order->created_at, 'customer_name' => $order->user->name,
                ]),
        ]]);
    }
}
