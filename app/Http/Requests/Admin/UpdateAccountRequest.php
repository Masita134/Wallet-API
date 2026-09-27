<?php

namespace App\Http\Requests\Admin;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Account $account */
        $account = $this->route('account');

        return [
            'user_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
                Rule::unique('accounts', 'user_id')
                    ->ignore($account->id),
            ],

            'cbu' => [
                'sometimes',
                'string',
                'digits:22',
                Rule::unique('accounts', 'cbu')
                    ->ignore($account->id),
            ],

            'balance' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'type' => [
                'sometimes',
                'in:savings,checking',
            ],

            'currency' => [
                'sometimes',
                'in:ARS,USD',
            ],
        ];
    }
}