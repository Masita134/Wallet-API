<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccountRequest;
use App\Http\Requests\Admin\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminAccountController extends Controller
{
    /**
     * Listar cuentas.
     */

    #[OA\Get(
        path: '/api/v1/admin/accounts',
        summary: 'Listar cuentas bancarias del sistema (Administrador)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Cantidad de registros por página', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: 'sort', in: 'query', required: false, description: 'Campo por el cual ordenar', schema: new OA\Schema(type: 'string', enum: ['id', 'user_id', 'cbu', 'balance', 'type', 'currency', 'created_at'], default: 'created_at')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Dirección del ordenamiento', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado de cuentas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'user_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'cbu', type: 'string', example: '1234567890123456789012'),
                                    new OA\Property(property: 'balance', type: 'number', format: 'float', example: 15000.50),
                                    new OA\Property(property: 'type', type: 'string', example: 'savings'),
                                    new OA\Property(property: 'currency', type: 'string', example: 'ARS'),
                                    new OA\Property(
                                        property: 'user',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 5),
                                            new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                                            new OA\Property(property: 'email', type: 'string', example: 'juan@example.com')
                                        ]
                                    )
                                ]
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer', example: 50)
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
                'in:id,user_id,cbu,balance,type,currency,created_at',
            ],

            'order' => [
                'sometimes',
                'in:asc,desc',
            ],
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        $accounts = Account::query()
            ->with('user')
            ->orderBy($sort, $order)
            ->paginate($perPage);

        $accounts->getCollection()->each(
            function (Account $account): void {
                if ($account->user) {
                    $account->user->makeHidden([
                        'password',
                        'remember_token',
                    ]);
                }
            }
        );

        return response()->json($accounts);
    }

    /**
     * Crear una cuenta.
     */

    #[OA\Post(
        path: '/api/v1/admin/accounts',
        summary: 'Crear una nueva cuenta bancaria (Administrador)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'cbu', 'balance', 'type', 'currency'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', description: 'ID del usuario dueño de la cuenta', example: 8),
                    new OA\Property(property: 'cbu', type: 'string', description: 'CBU único de 22 dígitos', example: '9876543210987654321098'),
                    new OA\Property(property: 'balance', type: 'number', format: 'float', minimum: 0, example: 5000.00),
                    new OA\Property(property: 'type', type: 'string', enum: ['savings', 'checking'], example: 'savings'),
                    new OA\Property(property: 'currency', type: 'string', enum: ['ARS', 'USD'], example: 'ARS')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cuenta creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cuenta creada correctamente.'),
                        new OA\Property(property: 'account', type: 'object', additionalProperties: true)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Error de validación (Ej: el usuario ya tiene cuenta, CBU duplicado)', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]

    public function store(
        StoreAccountRequest $request
    ): JsonResponse {
        $account = Account::create(
            $request->validated()
        );

        $account->load('user');

        if ($account->user) {
            $account->user->makeHidden([
                'password',
                'remember_token',
            ]);
        }

        return response()->json([
            'message' => 'Cuenta creada correctamente.',
            'account' => $account,
        ], 201);
    }

    /**
     * Consultar una cuenta.
     */

    #[OA\Get(
        path: '/api/v1/admin/accounts/{account}',
        summary: 'Consultar detalles de una cuenta específica (Administrador)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'account',
                in: 'path',
                required: true,
                description: 'ID de la cuenta a consultar',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuenta obtenida correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'account', type: 'object', additionalProperties: true)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'Cuenta no encontrada')
        ]
    )]

    public function show(Account $account): JsonResponse
    {
        $account->load('user');

        if ($account->user) {
            $account->user->makeHidden([
                'password',
                'remember_token',
            ]);
        }

        return response()->json([
            'account' => $account,
        ]);
    }

    /**
     * Actualizar una cuenta.
     */

    #[OA\Put(
        path: '/api/v1/admin/accounts/{account}',
        summary: 'Actualizar los datos de una cuenta existente (Administrador)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'account',
                in: 'path',
                required: true,
                description: 'ID de la cuenta a actualizar',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 8),
                    new OA\Property(property: 'cbu', type: 'string', example: '9876543210987654321098'),
                    new OA\Property(property: 'balance', type: 'number', format: 'float', minimum: 0, example: 7500.50),
                    new OA\Property(property: 'type', type: 'string', enum: ['savings', 'checking'], example: 'checking'),
                    new OA\Property(property: 'currency', type: 'string', enum: ['ARS', 'USD'], example: 'USD')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuenta actualizada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cuenta actualizada correctamente.'),
                        new OA\Property(property: 'account', type: 'object', additionalProperties: true)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'Cuenta no encontrada'),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]

    public function update(
        UpdateAccountRequest $request,
        Account $account
    ): JsonResponse {
        $account->update(
            $request->validated()
        );

        $account->load('user');

        if ($account->user) {
            $account->user->makeHidden([
                'password',
                'remember_token',
            ]);
        }

        return response()->json([
            'message' => 'Cuenta actualizada correctamente.',
            'account' => $account,
        ]);
    }

    /**
     * Eliminar una cuenta.
     */

    #[OA\Delete(
        path: '/api/v1/admin/accounts/{account}',
        summary: 'Eliminar una cuenta del sistema (Administrador)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'account',
                in: 'path',
                required: true,
                description: 'ID de la cuenta a eliminar',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuenta eliminada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cuenta eliminada correctamente.')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'Cuenta no encontrada')
        ]
    )]

    public function destroy(Account $account): JsonResponse
    {
        $account->delete();

        return response()->json([
            'message' => 'Cuenta eliminada correctamente.',
        ]);
    }
}