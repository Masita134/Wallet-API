<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Movement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class TransferController extends Controller
{
    #[OA\Post(
        path: '/api/v1/transfers',
        summary: 'Realizar una transferencia a otra cuenta',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['destination_cbu', 'amount'],
                properties: [
                    new OA\Property(
                        property: 'destination_cbu',
                        type: 'string',
                        example: '1255774332638486003143'
                    ),
                    new OA\Property(
                        property: 'amount',
                        type: 'number',
                        format: 'float',
                        example: 50.00
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transferencia realizada correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Transferencia realizada correctamente.'
                        ),
                        new OA\Property(
                            property: 'transfer',
                            properties: [
                                new OA\Property(
                                    property: 'destination_cbu',
                                    type: 'string',
                                    example: '1255774332638486003143'
                                ),
                                new OA\Property(
                                    property: 'amount',
                                    type: 'number',
                                    example: 50
                                )
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'balance',
                            type: 'string',
                            example: '0.00'
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
                response: 404,
                description: 'Cuenta a transferir no encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountNotFoundError')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación o regla de negocio',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]

    public function store(Request $request)
    {
        $data = $request->validate([
            'destination_cbu' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $user = auth('api')->user();

        $sourceAccount = $user->account;

        $destinationAccount = Account::where(
            'cbu',
            $data['destination_cbu']
        )->first();

        if (!$destinationAccount) {
            return response()->json([
                'message' => 'La cuenta destino no existe.'
            ], 404);
        }

        if ($sourceAccount->id === $destinationAccount->id) {
            return response()->json([
                'message' => 'No puedes transferir dinero a tu propia cuenta.'
            ], 422);
        }

        if ($sourceAccount->balance < $data['amount']) {
            return response()->json([
                'message' => 'Saldo insuficiente.'
            ], 422);
        }

        $result = DB::transaction(function () use (
            $sourceAccount,
            $destinationAccount,
            $data
        ) {
            $accounts = Account::whereIn('id', [
                $sourceAccount->id,
                $destinationAccount->id
            ])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $source = $accounts[$sourceAccount->id];
            $destination = $accounts[$destinationAccount->id];

            if ($source->balance < $data['amount']) {
                throw new \RuntimeException('Saldo insuficiente.');
            }

            $source->balance -= $data['amount'];
            $destination->balance += $data['amount'];

            $source->save();
            $destination->save();

            $source->movements()->create([
                'type' => Movement::TYPE_TRANSFER_OUT,
                'amount' => $data['amount'],
            ]);

            $destination->movements()->create([
                'type' => Movement::TYPE_TRANSFER_IN,
                'amount' => $data['amount'],
            ]);

            return [
                'source' => $source,
                'destination' => $destination,
            ];
        });

        return response()->json([
            'message' => 'Transferencia realizada correctamente.',
            'transfer' => [
                'destination_cbu' => $result['destination']->cbu,
                'amount' => $data['amount'],
            ],
            'balance' => $result['source']->balance,
        ]);
    }
}