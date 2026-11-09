<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCartRequest extends FormRequest
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
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id', Rule::unique('carts', 'user_id')->ignore((int) $this->route('cart'))],
            'guest_token' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('carts', 'guest_token')->ignore((int) $this->route('cart'))],
        ];
    }
}
