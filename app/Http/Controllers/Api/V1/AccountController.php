<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autorizado.'
            ], 401);
        }

        $account = $user->account;

        if (!$account) {
            return response()->json([
                'message' => 'El usuario no posee una cuenta asociada.'
            ], 404);
        }

        return response()->json([
            'account' => [
                'cbu' => $account->cbu,
                'balance' => number_format((float) $account->balance, 2, '.', ''),
                'type' => $account->type,
                'currency' => $account->currency,
            ],
        ], 200);
    }
}