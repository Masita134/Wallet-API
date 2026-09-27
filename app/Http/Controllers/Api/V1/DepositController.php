<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DepositController extends Controller
{
    #[OA\Post(
        path: '/api/v1/deposits',
        summary: 'Depositar dinero en la cuenta del usuario autenticado',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(
                        property: 'amount',
                        type: 'number',
                        format: 'float',
                        exclusiveMinimum: 0, //<-- refleja exactamente la regla del gt:0 que esta en DepositRequest!!
                        example: 50.00
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Depósito realizado correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/Account')
                ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')
                ),
            new OA\Response(
                response: 404,
                description: 'Cuenta no encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountNotFoundError')
                ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
                )
        ]
    )]

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