<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
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

    public function store(StorePaymentRequest $request)
    {
        return (new PaymentResource($this->paymentService->create($request->validated())))
            ->response()->setStatusCode(201);
    }

    public function update(UpdatePaymentRequest $request, int $id)
    {
        return new PaymentResource($this->paymentService->update($id, $request->validated('status'), $request->user()));
    }

    public function pay(Request $request, int $id)
    {
        return new OrderResource($this->paymentService->pay($id, $request->user()));
    }
}
