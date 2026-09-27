<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MovementController extends Controller
{
    #[OA\Get(
        path: '/api/v1/movements',
        summary: 'Listar los movimientos de la cuenta del usuario autenticado',
        security:[['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Número de página para la paginación',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Cantidad de  movimientos por página',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Orden de los movimientos por fecha',
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado de movimientos obtenido correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'type', type: 'string', example: 'deposit'),
                                    new OA\Property(property: 'amount', type: 'string', example: '50.00'),
                                    new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2026-09-26T04:44:19.000000Z'),
                                    new OA\Property(property: 'counterparty_cbu', type: 'string', nullable: true, example: null)
                                ]
                            )
                        ),
                        new OA\Property(property: 'first_page_url', type: 'string', example: 'http://127.0.0.1:8000/api/v1/movements?page=1'),
                        new OA\Property(property: 'from', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page_url', type: 'string', example: 'http://127.0.0.1:8000/api/v1/movements?page=1'),
                        new OA\Property(
                            property: 'links',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'url', type: 'string', nullable: true),
                                    new OA\Property(property: 'label', type: 'string', example: '&laquo; Previous'),
                                    new OA\Property(property: 'active', type: 'boolean', example: false)
                                ]
                            )
                        ),
                        new OA\Property(property: 'new_page_url', type: 'string', nullable: true),
                        new OA\Property(property: 'path', type: 'string', example: '2026-09-26T04:44:19.000000Z'),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'prev_page_url', type: 'string', nullable: true),
                        new OA\Property(property: 'to', type: 'integer', example: 4),
                        new OA\Property(property: 'total', type: 'integer', example: 4)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')
            ),
            new OA\Response(
                response: 404,
                description: 'El usuario no posee una cuenta asociada',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountNotFoundError')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación en los parámetros de búsqueda',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'order' => ['sometimes', 'in:asc,desc'],
        ]);

        $user = auth('api')->user();

        $account = $user->account;

        if (!$account) {
            return response()->json([
                'message' => 'El usuario no posee una cuenta asociada.',
            ], 404);
        }

        $perPage = $validated['per_page'] ?? 15;
        $order = $validated['order'] ?? 'desc';

        $movements = Movement::query()
            ->where('account_id', $account->id)
            ->orderBy('created_at', $order)
            ->orderBy('id', $order)
            ->paginate($perPage);

        $movements->through(function (Movement $movement): array {
            return [
                'type' => $movement->type,
                'amount' => $movement->amount,
                'date' => $movement->created_at?->toISOString(),
                'counterparty_cbu' => $movement->counterparty_cbu,
            ];
        });

        return response()->json($movements);
    }
}
