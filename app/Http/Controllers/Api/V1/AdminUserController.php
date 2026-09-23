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

class AdminUserController extends Controller
{
    /**
     * Listar usuarios.
     */
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