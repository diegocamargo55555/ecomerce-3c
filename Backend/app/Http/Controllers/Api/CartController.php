<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function current(Request $request)
    {
        // Visitante que ainda não adicionou nada: nenhum carrinho é gravado só por abrir a loja.
        if (! $request->user() && ! $request->session()->has('cart_token')) {
            $empty = (new Cart)->setRelation('items', new Collection)
                ->forceFill(['total' => '0.00', 'item_count' => 0]);

            return (new CartResource($empty))->response()->setStatusCode(200);
        }

        return (new CartResource($this->cartService->details($this->cartService->current($request))))
            ->response()->setStatusCode(200);
    }

    public function index()
    {
        return CartResource::collection($this->cartService->listAll());
    }

    public function store(StoreCartRequest $request)
    {
        return new CartResource($this->cartService->create($request->validated()));
    }

    public function show(string $id)
    {
        return new CartResource($this->cartService->findById($id));
    }

    public function update(UpdateCartRequest $request, int $id)
    {
        $cart = $this->cartService->update($id, $request->validated());
        return new CartResource($cart);
    }

    public function destroy(string $id)
    {
        $this->cartService->delete($id);

        return response()->noContent();
    }
}
