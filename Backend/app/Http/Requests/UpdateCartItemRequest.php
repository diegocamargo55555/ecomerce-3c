<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Cart_item;
use Illuminate\Validation\Rule;

class UpdateCartItemRequest extends FormRequest
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
        $item = Cart_item::findOrFail((int) $this->route('cart_item'));

        return [
            'cart_id' => ['sometimes', 'required', 'integer', 'exists:carts,id', Rule::unique('cart_items', 'cart_id')->where('product_id', $this->input('product_id', $item->product_id))->ignore($item->id)],
            'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id', Rule::unique('cart_items', 'product_id')->where('cart_id', $this->input('cart_id', $item->cart_id))->ignore($item->id)],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
        ];
    }
}
