<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccountRequest;
use App\Http\Requests\Admin\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAccountController extends Controller
{
    /**
     * Listar cuentas.
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
    public function destroy(Account $account): JsonResponse
    {
        $account->delete();

        return response()->json([
            'message' => 'Cuenta eliminada correctamente.',
        ]);
    }
}