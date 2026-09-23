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
        $query = Movement::with('account');

        // filled() asegura que el parámetro exista y no sea un string vacío
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->input('account_id'));
        }

        if ($request->filled('user_id')) {
            $query->whereHas('account', function ($q) use ($request) {
                $q->where('user_id', $request->input('user_id'));
            });
        }

        // Ordena descendente por defecto; permite 'asc' si se solicita explícitamente
        $direction = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy('created_at', $direction);

        $movements = $query->paginate(15);

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