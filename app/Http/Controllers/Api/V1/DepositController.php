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

        DB::transaction(function () use ($account, $amount): void {
            $account->increment('balance', $amount);

            $account->movements()->create([
                'type' => Movement::TYPE_DEPOSIT,
                'amount' => $amount,
            ]);
        });

        return response()->json([
            'balance' => number_format(
                (float) $account->fresh()->balance,
                2,
                '.',
                ''
            ),
        ], 200);
    }
}
