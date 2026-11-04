<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'status' => ['sometimes', 'required', 'string', 'in:pendente,pago,cancelado'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
