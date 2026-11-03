<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'users_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'required', 'string', 'in:pendente,pago,cancelado'],
            'subtotal' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'total' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'shipping_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }
}
