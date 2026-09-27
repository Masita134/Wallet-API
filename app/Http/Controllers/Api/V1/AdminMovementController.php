<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAdminMovementRequest;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * IMPORTANTE (WAL-018):
 * Las operaciones de edición (update) y eliminación (destroy) en este controller
 * modifican ÚNICAMENTE el historial de movimientos y NO recalculan el saldo (balance) 
 * de la cuenta asociada, a fin de no confundir este CRUD con flujos operacionales 
 * como depósitos o transferencias.
 */

class AdminMovementController extends Controller
{
    
    #[OA\Get(
        path: '/api/v1/admin/movements',
        summary: 'Listar todos los movimientos (Administrador)',
        description: 'Obtiene el historial de movimientos de forma paginada. Permite filtrar por cuenta o por usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Cantidad de registros por página', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: 'sort', in: 'query', required: false, description: 'Campo por el cual ordenar', schema: new OA\Schema(type: 'string', enum: ['id', 'account_id', 'type', 'amount', 'counterparty_cbu', 'created_at'], default: 'created_at')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Dirección del ordenamiento', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'account_id', in: 'query', required: false, description: 'Filtrar por ID de cuenta', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, description: 'Filtrar por ID de usuario dueño de la cuenta', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado de movimientos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 10),
                                    new OA\Property(property: 'account_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'type', type: 'string', example: 'deposit'),
                                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 1500.00),
                                    new OA\Property(property: 'counterpart_cbu', type: 'string', nullable: true, example: '1234567890123456789012'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'account', type: 'object', additionalProperties: true)
                                ]
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer', example: 150)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError'))
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],

            'sort' => [
                'sometimes',
                'in:id,account_id,type,amount,counterparty_cbu,created_at',
            ],

            'order' => [
                'sometimes',
                'in:asc,desc',
            ],

            'account_id' => [
                'sometimes',
                'integer',
                'exists:accounts,id',
            ],

            'user_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
            ],
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        $query = Movement::with('account');

        if (isset($validated['account_id'])) {
            $query->where('account_id', $validated['account_id']);
        }

        if (isset($validated['user_id'])) {
            $query->whereHas('account', function ($q) use ($validated) {
                $q->where('user_id', $validated['user_id']);
            });
        }

        $query->orderBy($sort, $order);

        $movements = $query->paginate($perPage);

        return response()->json($movements);
    }

    #[OA\Post(
        path: '/api/v1/admin/movements',
        summary: 'Registrar un movimiento manual (Administrador)',
        description: 'IMPORTANTE: Esta operación registra un movimiento en el historial pero NO recalcula el saldo de la cuenta asociada.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['account_id', 'type', 'amount'],
                properties: [
                    new OA\Property(property: 'account_id', type: 'integer', description: 'ID de la cuenta a la que se asocia el movimiento', example: 5),
                    new OA\Property(property: 'type', type: 'string', enum: ['deposit', 'transfer_in', 'transfer_out'], example: 'deposit'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 0.01, example: 5000.50),
                    new OA\Property(property: 'counterpart_cbu', type: 'string', maxLength: 22, nullable: true, description: 'CBU de la contraparte (opcional)', example: '9876543210987654321098')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Movimiento creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 11),
                        new OA\Property(property: 'account_id', type: 'integer', example: 5),
                        new OA\Property(property: 'type', type: 'string', example: 'deposit'),
                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 5000.50),
                        new OA\Property(property: 'account', type: 'object', additionalProperties: true)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]

    public function store(SaveAdminMovementRequest $request): JsonResponse
    {
        $movement = Movement::create($request->validated());
        $movement->load('account');

        return response()->json($movement, 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/movements/{movement}',
        summary: 'Consultar detalles de un movimiento específico (Administrador)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'movement',
                in: 'path',
                required: true,
                description: 'ID del movimiento a consultar',
                schema: new OA\Schema(type: 'integer', example: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimiento obtenido correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 10),
                        new OA\Property(property: 'account_id', type: 'integer', example: 5),
                        new OA\Property(property: 'type', type: 'string', example: 'deposit'),
                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 1500.00),
                        new OA\Property(property: 'counterpart_cbu', type: 'string', nullable: true, example: null),
                        new OA\Property(property: 'account', type: 'object', additionalProperties: true)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'Movimiento no encontrado')
        ]
    )]

    public function show(Movement $movement): JsonResponse
    {
        $movement->load('account');

        return response()->json($movement);
    }


    #[OA\Put(
        path: '/api/v1/admin/movements/{movement}',
        summary: 'Actualizar un movimiento del historial (Administrador)',
        description: 'IMPORTANTE: Edita únicamente el registro histórico. NO recalcula ni altera el saldo de la cuenta.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'movement',
                in: 'path',
                required: true,
                description: 'ID del movimiento a actualizar',
                schema: new OA\Schema(type: 'integer', example: 10)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['account_id', 'type', 'amount'],
                properties: [
                    new OA\Property(property: 'account_id', type: 'integer', example: 5),
                    new OA\Property(property: 'type', type: 'string', enum: ['deposit', 'transfer_in', 'transfer_out'], example: 'deposit'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 0.01, example: 2500.00),
                    new OA\Property(property: 'counterpart_cbu', type: 'string', maxLength: 22, nullable: true, example: '9876543210987654321098')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimiento actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 10),
                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 2500.00),
                        new OA\Property(property: 'account', type: 'object', additionalProperties: true)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'Movimiento no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]

    public function update(SaveAdminMovementRequest $request, Movement $movement): JsonResponse
    {
        $movement->update($request->validated());
        $movement->load('account');

        return response()->json($movement);
    }

    #[OA\Delete(
        path: '/api/v1/admin/movements/{movement}',
        summary: 'Eliminar un movimiento del historial (Administrador)',
        description: 'IMPORTANTE: Elimina el registro físico del historial, pero NO reintegra ni descuenta el saldo de la cuenta asociada.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'movement',
                in: 'path',
                required: true,
                description: 'ID del movimiento a eliminar',
                schema: new OA\Schema(type: 'integer', example: 10)
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'Movimiento eliminado exitosamente (No Content)'),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'Movimiento no encontrado')
        ]
    )]

    public function destroy(Movement $movement): JsonResponse
    {
        $movement->delete();

        return response()->json(null, 204);
    }
}