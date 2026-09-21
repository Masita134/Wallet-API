<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

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

    public function test_register_does_not_expose_sensitive_user_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');
    }

    public function test_authenticated_profile_does_not_expose_sensitive_user_data(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(200)
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');
    }
}
