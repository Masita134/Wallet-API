<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->account()->create([
                'cbu' => $this->generateUniqueCBU(),
                'balance' => 0.00,
            ]);

            return $user;
        });

        $user->load('account');

        $user->makeHidden(['password']);

        return response()->json([
            'message' => 'Usuario registrado exitosamente.',
            'user' => $user,
        ], 201);
    }

    private function generateUniqueCBU()
    {
        do {
            $cbu = '';
            for ($i = 0; $i < 22; $i++) {
                $cbu .= mt_rand(0, 9); //<-- El mt_rand genera un número aleatorio entre 0 y 9!
                                       //    Y el .= sirve para unir el número generado a la variable $cbu, que al final tendrá 22 dígitos.
            }
        } while (Account::where('cbu', $cbu)->exists());

        return $cbu;
    }
}