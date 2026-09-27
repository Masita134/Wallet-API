<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('accounts', 'user_id'),
            ],
            'cbu' => [
                'required',
                'string',
                'digits:22',
                'unique:accounts,cbu',
            ],
            'balance' => [
                'required',
                'numeric',
                'min:0',
            ],
            'type' => [
                'required',
                'in:savings,checking',
            ],
            'currency' => [
                'required',
                'in:ARS,USD',
            ],
        ];
    }
}