<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /**
     * Registrar un nuevo usuario.
     */
    public function register(RegisterUserRequest $request)
    {
        $user = DB::transaction(function () use ($request) {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_admin' => false,
            ]);

            $user->account()->create([
                'cbu' => $this->generateUniqueCBU(),
                'balance' => 0.00,
            ]);

            return $user;
        });

        $user->load('account');

        $user->makeHidden([
            'password',
            'remember_token',
        ]);

        return response()->json([
            'message' => 'Usuario registrado exitosamente.',
            'user' => $user,
        ], 201);
    }

    /**
     * Iniciar sesión.
     */
   public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $normalizedEmail = strtolower(trim($credentials['email']));

        $key = 'login:' . hash(
            'sha256',
            $normalizedEmail . '|' . $request->ip()
        );

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Demasiados intentos de inicio de sesión.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }

        if (!$token = auth('api')->attempt($credentials)) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        RateLimiter::clear($key);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Obtener el usuario autenticado.
     */
    public function me()
    {
        $user = auth('api')
            ->user()
            ->load('account');

        $user->makeHidden([
            'password',
            'remember_token',
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Cerrar sesión.
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Generar CBU único de 22 dígitos.
     */
    private function generateUniqueCBU(): string
    {
        do {
            $cbu = '';

            for ($i = 0; $i < 22; $i++) {
                $cbu .= mt_rand(0, 9);
            }
        } while (Account::where('cbu', $cbu)->exists());

        return $cbu;
    }
}
