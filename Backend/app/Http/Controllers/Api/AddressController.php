<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AddressController extends Controller
{
    private function fields(Request $request): array
    {
        return $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'regex:/^\d{5}-?\d{3}$/'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:30'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
        ]);
    }

    public function index(Request $request)
    {
        return AddressResource::collection($request->user()->addresses()->get());
    }

    public function store(Request $request)
    {
        return (new AddressResource($request->user()->addresses()->create($this->fields($request))))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id)
    {
        return new AddressResource($request->user()->addresses()->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        if ($address->orders()->exists()) {
            throw ValidationException::withMessages(['address' => 'Este endereço já foi usado em um pedido. Cadastre um novo endereço.']);
        }
        $address->update($this->fields($request));
        return new AddressResource($address);
    }

    public function destroy(Request $request, int $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        if ($address->orders()->exists()) {
            throw ValidationException::withMessages(['address' => 'Endereços usados em pedidos não podem ser excluídos.']);
        }
        $address->delete();
        return response()->noContent();
    }
}
