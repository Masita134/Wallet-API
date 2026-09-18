<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovementController extends Controller
{
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
