<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index()
    {
        return PaymentResource::collection($this->paymentService->listAll());
    }

    public function show(int $id)
    {
        return new PaymentResource($this->paymentService->findById($id));
    }

    public function pay(Request $request, int $id)
    {
        return new OrderResource($this->paymentService->pay($id, $request->user()));
    }
}
