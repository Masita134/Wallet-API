<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\SimulateFixedTermRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class FixedTermController extends Controller
{
    #[OA\Post(
        path: '/api/v1/investments/fixed-term/simulate',
        summary: 'Simular el rendimiento de un plazo fijo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'duration'],
                properties: [
                    new OA\Property(
                        property: 'amount',
                        type: 'number',
                        format: 'float',
                        minimum: 0.01,
                        example: 100000.50
                    ),
                    new OA\Property(
                        property: 'duration',
                        type: 'integer',
                        minimum: 30,
                        maximum: 365,
                        example: 30
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Simulación calculada correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'creation_date', type: 'string', example: '2026-09-27 10:00:00'),
                        new OA\Property(property: 'end_date', type: 'string', example: '2026-10-27 10:00:00'),
                        new OA\Property(property: 'amount_invested', type: 'number', format: 'float', example: 100000.50),
                        new OA\Property(property: 'interest_earned', type: 'number', format: 'float', example: 2465.75),
                        new OA\Property(property: 'total_to_collect', type: 'number', format: 'float', example: 102465.75),
                        new OA\Property(
                            property: 'details',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'tna', type: 'string', example: '30%'),
                                new OA\Property(property: 'formula', type: 'string', example: 'Interés = Monto * (TNA / 100) * (Plazo / 365)')
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
                description: 'Error de validación (monto faltante o plazo fuera del rango de 30 a 365 días)',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]

    public function simulate(SimulateFixedTermRequest $request): JsonResponse
    {
        $amount = (float) $request->validated('amount');
        $duration = (int) $request->validated('duration');

        $tna = config('wallet.fixed_term.tna', 30); //lee la tasa desde la configuración

        //Se cálcula el interés simple base 365 días. Para conseguir el interés se hace el monto multiplicado por el TNA (dividido 100) y multiplicado por el plazo (dividido por el 365)
        $interest = $amount * ($tna / 100) * ($duration / 365);
        $total = $amount + $interest;

        $creationDate = now();
        $endDate = clone $creationDate;
        $endDate->addDays($duration);

        return response()->json([
            'creation_date' => $creationDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
            'amount_invested' => round($amount, 2),
            'interest_earned' => round($interest, 2),
            'total_to_collect' => round($total, 2),
            'details' => [
                'tna' => $tna . '%',
                'formula' => 'Interés = Monto * (TNA / 100) * (Plazo / 365)'
            ]
        ]);
    }
}
