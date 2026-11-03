<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function index()
    {
        return response()->json($this->cartService->listAll());
    }

    public function store(Request $request)
    {
        $cart = $this->cartService->create($request->all());

        return response()->json($cart, 201);
    }

    public function show(string $id)
    {
        return response()->json($this->cartService->findById($id));
    }

    public function update(Request $request, string $id)
    {
        $cart = $this->cartService->update($id, $request->all());

        return response()->json($cart);
    }

    public function destroy(string $id)
    {
        $this->cartService->delete($id);

        return response()->noContent();
    }
}
