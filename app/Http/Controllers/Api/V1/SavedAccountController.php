<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
<<<<<<< HEAD
use Illuminate\Http\Request;
=======
use OpenApi\Attributes as OA;
>>>>>>> origin/dev


class SavedAccountController extends Controller
{
    #[OA\Get(
        path: '/api/v1/cbu',
        summary: 'Listar los CBUs y titulares guardados por el usuario autenticado',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de cuentas guardadas obtenido correctamente',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'cbu', type: 'string', example: '1255774332638486003143'),
                            new OA\Property(property: 'titular', type: 'string', example: 'Juan Pérez')
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')
            )
        ]
    )]

    public function index(): JsonResponse
    {
        $user = auth('api')->user();

        $savedAccounts = $user->savedAccounts()->with('user')->get();

        $data = $savedAccounts->map(function ($account) {
            return [
                'cbu' => $account->cbu,
                'titular' => $account->user->name ?? 'Sin titular.',
            ];
        });

        return response()->json($data, 200);
    }

    #[OA\Post(
        path: '/api/v1/cbu/{cbu}/users/{idUser}',
        summary: 'Guardar una cuenta (CBU) en la agenda del usuario',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'cbu',
                in: 'path',
                required: true,
                description: 'CBU de la cuenta a agendar',
                schema: new OA\Schema(type: 'string', example: '1255774332638486003143')
            ),
            new OA\Parameter(
                name: 'idUser',
                in: 'path',
                required: true,
                description: 'ID del usuario autenticado',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuenta guardada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cuenta guardada exitosamente.')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')
            ),
            new OA\Response(
                response: 403,
                description: 'El ID de la ruta no coincide con el del usuario autenticado',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'No puedes modificar la lista de otro usuario.')])
            ),
            new OA\Response(
                response: 404,
                description: 'El CBU ingresado no existe en el sistema',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'La cuenta no existe.')])
            ),
            new OA\Response(
                response: 422,
                description: 'Error de negocio (intentar guardar la cuenta propia o una ya guardada)',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'La cuenta ya está guardada.')])
            )
        ]
    )]

    public function store(Request $request, int $idUser): JsonResponse
    {
        $cbu = $request->input('cbu');
        $user = auth('api')->user();

        if ($user->id !== $idUser) {
            return response()->json([
                'message' => 'No puedes modificar la lista de otro usuario.',
            ], 403);
        }

        $account = Account::where('cbu', $cbu)->first();

        if (!$account) {
            return response()->json([
                'message' => 'La cuenta no existe.',
            ], 404);
        }
        if ($account->user_id === $user->id) {
            return response()->json([
                'message' => 'No puedes guardar tu propia cuenta.',
            ], 422);
        }
        if ($user->savedAccounts()->where('account_id', $account->id)->exists()) {
            return response()->json([
                'message' => 'La cuenta ya está guardada.',
            ], 422);
        }
        $user->savedAccounts()->attach($account->id);

        return response()->json([
            'message' => 'Cuenta guardada exitosamente.',
        ], 200);
    }

    #[OA\Delete(
        path: '/api/v1/cbu/{cbu}/users/{idUser}',
        summary: 'Eliminar un CBU de la agenda del usuario',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'cbu',
                in: 'path',
                required: true,
                description: 'CBU de la cuenta a eliminar',
                schema: new OA\Schema(type: 'string', example: '1255774332638486003143')
            ),
            new OA\Parameter(
                name: 'idUser',
                in: 'path',
                required: true,
                description: 'ID del usuario autenticado',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CBU removido de la agenda exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'CBU removido exitosamente.')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')
            ),
            new OA\Response(
                response: 403,
                description: 'El ID de la ruta no coincide con el del usuario autenticado',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'La cuenta no se encuentra en tu lista de guardados.')])
            )
        ]
    )]

    public function destroy(string $cbu, int $idUser): JsonResponse
    {
        $user = auth('api')->user();

        if ($user->id !== $idUser) {
            return response()->json([
                'message' => 'No puedes modificar la lista de otro usuario.',
            ], 403);
        }

        $account = Account::where('cbu', $cbu)->first();

        if (!$account) {
            return response()->json([
                'message' => 'La cuenta no existe.',
            ], 404);
        }

        if (!$user->savedAccounts()->where('account_id', $account->id)->exists()) {
            return response()->json([
                'message' => 'La cuenta no se encuentra en tu lista de guardados.',
            ], 404);
        }

        $user->savedAccounts()->detach($account->id);

        return response()->json([
            'message' => 'CBU removido exitosamente.',
        ], 200);
    }
}
