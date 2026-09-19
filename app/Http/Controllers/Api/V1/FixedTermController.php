<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\SimulateFixedTermRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FixedTermController extends Controller
{
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
