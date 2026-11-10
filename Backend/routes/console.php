<?php

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pedidos sem pagamento reservam estoque; depois de 30 minutos ele volta à venda.
Artisan::command('orders:cancel-unpaid', function (OrderService $orders) {
    Order::where('status', 'pendente')->where('created_at', '<', now()->subMinutes(30))
        ->with('user')->get()
        ->each(fn (Order $order) => $orders->cancel($order->id, $order->user));
})->purpose('Cancela pedidos pendentes há mais de 30 minutos e devolve o estoque');

Schedule::command('orders:cancel-unpaid')->everyFiveMinutes()->withoutOverlapping();
