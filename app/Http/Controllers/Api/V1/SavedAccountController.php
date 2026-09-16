<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;


class SavedAccountController extends Controller
{
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
}