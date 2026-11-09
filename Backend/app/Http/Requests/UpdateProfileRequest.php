<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['missing'],
            'id' => ['missing'],
            'password' => ['missing'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'current_password' => [Rule::requiredIf($this->has('email') && $this->input('email') !== $this->user()->email), 'nullable', 'string', 'current_password:web'],
        ];
    }
}
