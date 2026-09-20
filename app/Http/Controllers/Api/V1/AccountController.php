<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class AccountController extends Controller
{
    public function show()
    {
        $account = auth('api')->user()->account;

        return response()->json([
            'account' => [
                'cbu' => $account->cbu,
                'balance' => $account->balance,
                'type' => $account->type,
                'currency' => $account->currency,
            ],
        ]);
    }
}