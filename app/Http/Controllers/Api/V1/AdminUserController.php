<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AdminUserController extends Controller
{
    /**
     * Listar usuarios.
     */
    #[OA\Get(
        path: '/api/v1/admin/users',
        summary: 'Listar usuarios del sistema (Administrador)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Cantidad de registros por página', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: 'sort', in: 'query', required: false, description: 'Campo por el cual ordenar', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'email', 'age', 'created_at'], default: 'created_at')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Dirección del ordenamiento', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado de usuarios',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Usuario de Prueba'),
                                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'usuario@example.com'),
                                    new OA\Property(property: 'age', type: 'integer', nullable: true, example: 22),
                                    new OA\Property(property: 'image', type: 'string', nullable: true, example: 'profiles/default.jpg')
                                ]
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer', example: 12)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError'))
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
                'in:id,name,email,age,created_at',
            ],
            'order' => [
                'sometimes',
                'in:asc,desc',
            ],
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        $users = User::query()
            ->orderBy($sort, $order)
            ->paginate($perPage);

        $users->getCollection()->each(function (User $user): void {
            $user->makeHidden([
                'password',
                'remember_token',
            ]);
        });

        return response()->json($users);
    }

    /**
     * Crear usuario y su cuenta asociada.
     */
    #[OA\Post(
        path: '/api/v1/admin/users',
        summary: 'Crear un nuevo usuario y su cuenta bancaria (Administrador)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Nuevo Usuario'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'nuevo@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'Secreta123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', minLength: 8, example: 'Secreta123'),
                    new OA\Property(property: 'age', type: 'integer', minimum: 0, nullable: true, example: 25),
                    new OA\Property(property: 'image', type: 'string', maxLength: 255, nullable: true, example: 'profiles/avatar.png')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Usuario creado correctamente.'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 5),
                                new OA\Property(property: 'name', type: 'string', example: 'Nuevo Usuario'),
                                new OA\Property(property: 'email', type: 'string', example: 'nuevo@example.com'),
                                new OA\Property(
                                    property: 'account',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'cbu', type: 'string', example: '1234567890123456789012'),
                                        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 0.00)
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Error de validación (email duplicado, contraseñas no coinciden, etc.)', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'age' => $request->age,
                'image' => $request->image,
            ]);

            $user->account()->create([
                'cbu' => $this->generateUniqueCBU(),
                'balance' => 0.00,
            ]);

            return $user;
        });

        $user->load('account');

        $user->makeHidden([
            'password',
            'remember_token',
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'user' => $user,
        ], 201);
    }

    /**
     * Consultar un usuario.
     */

    #[OA\Get(
        path: '/api/v1/admin/users/{user}',
        summary: 'Consultar detalles de un usuario específico (Administrador)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'ID del usuario a consultar',
                schema: new OA\Schema(type: 'integer', example: 5)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario obtenido correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 5),
                                new OA\Property(property: 'name', type: 'string', example: 'Nuevo Usuario'),
                                new OA\Property(
                                    property: 'account',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'cbu', type: 'string', example: '1234567890123456789012'),
                                        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.50)
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'Usuario no encontrado')
        ]
    )]

    public function show(User $user): JsonResponse
    {
        $user->load('account');

        $user->makeHidden([
            'password',
            'remember_token',
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Actualizar un usuario.
     */

    #[OA\Put(
        path: '/api/v1/admin/users/{user}',
        summary: 'Actualizar los datos de un usuario existente (Administrador)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'ID del usuario a actualizar',
                schema: new OA\Schema(type: 'integer', example: 5)
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Nombre Actualizado'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'actualizado@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'NuevaClave123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', minLength: 8, example: 'NuevaClave123'),
                    new OA\Property(property: 'age', type: 'integer', minimum: 0, nullable: true, example: 26),
                    new OA\Property(property: 'image', type: 'string', maxLength: 255, nullable: true, example: 'profiles/nuevo-avatar.png')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Usuario actualizado correctamente.'),
                        new OA\Property(property: 'user', type: 'object', additionalProperties: true)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))
        ]
    )]

    public function update(
        UpdateUserRequest $request,
        User $user
    ): JsonResponse {
        $data = $request->validated();

        if (array_key_exists('password', $data)) {
            $data['password'] = Hash::make($data['password']);
        }

        /*
         * is_admin no forma parte de los campos aceptados.
         * Un administrador no puede modificar el rol administrativo
         * mediante este endpoint.
         */
        unset($data['is_admin']);

        $user->update($data);

        $user->load('account');

        $user->makeHidden([
            'password',
            'remember_token',
        ]);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'user' => $user,
        ]);
    }

    /**
     * Eliminar un usuario.
     */

    #[OA\Delete(
        path: '/api/v1/admin/users/{user}',
        summary: 'Eliminar un usuario del sistema (Administrador)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'ID del usuario a eliminar',
                schema: new OA\Schema(type: 'integer', example: 5)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario eliminado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Usuario eliminado correctamente.')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'Usuario no encontrado')
        ]
    )]

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    /**
     * Generar un CBU único de 22 dígitos.
     */
    private function generateUniqueCBU(): string
    {
        do {
            $cbu = '';

            for ($i = 0; $i < 22; $i++) {
                $cbu .= mt_rand(0, 9);
            }
        } while (Account::where('cbu', $cbu)->exists());

        return $cbu;
    }
}