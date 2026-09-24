<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAdminMovementRequest;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * IMPORTANTE (WAL-018):
 * Las operaciones de edición (update) y eliminación (destroy) en este controller
 * modifican ÚNICAMENTE el historial de movimientos y NO recalculan el saldo (balance) 
 * de la cuenta asociada, a fin de no confundir este CRUD con flujos operacionales 
 * como depósitos o transferencias.
 */

class AdminMovementController extends Controller
{
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

    public function store(SaveAdminMovementRequest $request): JsonResponse
    {
        $movement = Movement::create($request->validated());
        $movement->load('account');

        return response()->json($movement, 201);
    }

    public function show(Movement $movement): JsonResponse
    {
        $movement->load('account');

        return response()->json($movement);
    }

    public function update(SaveAdminMovementRequest $request, Movement $movement): JsonResponse
    {
        $movement->update($request->validated());
        $movement->load('account');

        return response()->json($movement);
    }

    public function destroy(Movement $movement): JsonResponse
    {
        $movement->delete();

        return response()->json(null, 204);
    }
}