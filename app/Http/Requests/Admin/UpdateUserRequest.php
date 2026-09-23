<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'password' => [
                'sometimes',
                'string',
                'min:8',
                'confirmed',
            ],

            'age' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],

            'image' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}