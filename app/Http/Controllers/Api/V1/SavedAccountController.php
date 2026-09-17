<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;


class SavedAccountController extends Controller
{
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


    public function store(string $cbu, int $idUser): JsonResponse
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