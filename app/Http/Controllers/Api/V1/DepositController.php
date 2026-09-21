<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function store(DepositRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $account = $user->account;

        $amount = $request->validated('amount');

        $movement = DB::transaction(function () use ($account, $amount): Movement {
            $account->increment('balance', $amount);

            return $account->movements()->create([
                'type' => Movement::TYPE_DEPOSIT,
                'amount' => $amount,
            ]);
        });

        return response()->json([
            'message' => 'Depósito realizado correctamente.',
            'movement_id' => $movement->id,
            'balance' => number_format(
                (float) $account->fresh()->balance,
                2,
                '.',
                ''
            ),
        ], 200);
    }
}