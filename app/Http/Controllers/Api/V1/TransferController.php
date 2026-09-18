<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Movement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
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