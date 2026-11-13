<?php

use App\Models\Cart;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:cancel-unpaid', function (OrderService $orders) {
    Order::where('status', 'pendente')->where('created_at', '<', now()->subMinutes(30))
        ->with('user')->get()
        ->each(fn (Order $order) => $orders->cancel($order->id, $order->user));
})->purpose('Cancela pedidos pendentes há mais de 30 minutos e devolve o estoque');

Schedule::command('orders:cancel-unpaid')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('carts:prune-guests', function () {
    $limit = now()->subWeek();
    Cart::whereNull('user_id')->where('created_at', '<', $limit)
        ->whereDoesntHave('items', fn ($items) => $items->where('updated_at', '>=', $limit))
        ->delete();
})->purpose('Remove carrinhos de visitante sem alterações há mais de 7 dias');

Schedule::command('carts:prune-guests')->daily();
