<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    #[OA\Get(
        path: '/api/v1/profile',
        summary: 'Obtener los datos del perfil del usuario autenticado',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil obtenido correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')
            )
        ]
    )]

    public function show(): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/profile',
        summary: 'Actualizar los datos del perfil (nombre, email, edad o imágen)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Juan Modificado'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'juan.modificado@example.com'),
                        new OA\Property(property: 'age', type: 'integer', minimum: 1, example: 22),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Imágen de perfil (JPG, PNG, WEBP. Máx 2MB)')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil actualizado correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Perfil actualizado correctamente.'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Juan Modificado'),
                                new OA\Property(property: 'email', type: 'string', example: 'jaun.modificado@example.com'),
                                new OA\Property(property: 'age', type: 'integer', example: 22),
                                new OA\Property(property: 'image', type: 'string', nullable: true, example: 'profiles/tUxg6A...jpg')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación (email en uso, formato de imágen incorrecto, etc)',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]

    public function update(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'age' => ['sometimes', 'integer', 'min:1'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }

            $validated['image'] = $request->file('image')->store('profiles', 'public');
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'age' => $user->age,
                'image' => $user->image,
            ],
        ]);
    }
    
    #[OA\Delete(
        path: '/api/v1/profile',
        summary: 'Eliminar de forma definitiva la cuenta del usuario autenticado',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil eliminado correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Perfil eliminado correctamente.')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')
            )
        ]
    )]

    public function destroy(): JsonResponse
    {
        $user = auth('api')->user();

        $user->delete();

        return response()->json([
            'message' => 'Perfil eliminado correctamente.',
        ]);
    }
}
