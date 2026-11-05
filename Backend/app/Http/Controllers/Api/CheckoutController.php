<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\ShopService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __invoke(Request $request, ShopService $shop)
    {
        $data = $request->validate(['address_id' => ['required', 'integer']]);
        return (new OrderResource($shop->checkout($request->user(), $data['address_id'])))
            ->response()->setStatusCode(201);
    }
}
