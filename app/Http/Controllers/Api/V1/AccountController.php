<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AccountController extends Controller
{
    #[OA\Get(
    path: '/api/v1/account',
    summary: 'Consultar la cuenta del usuario autenticado',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200, 
            description: 'Cuenta obtenida correctamente',
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
            )
        ]
    )]

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