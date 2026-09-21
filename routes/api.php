<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SavedAccountController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\DepositController;
use App\Http\Controllers\Api\V1\MovementController;
use App\Http\Controllers\Api\V1\FixedTermController;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Autenticación
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {

        Route::post('/register', [AuthController::class, 'register']);

        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:api')->group(function () {

            Route::get('/me', [AuthController::class, 'me']);

            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Rutas protegidas
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:api')->group(function () {

        Route::get('/profile', [ProfileController::class, 'show']);

        Route::put('/profile', [ProfileController::class, 'update']);

        Route::delete('/profile', [ProfileController::class, 'destroy']);

        Route::get('/account', [AccountController::class, 'show']);

        Route::post('/transfers', [TransferController::class, 'store']);

        Route::post('/cbu/{cbu}/users/{idUser}', [SavedAccountController::class, 'store']);

        Route::get('/cbu', [SavedAccountController::class, 'index']);

        Route::delete('/cbu/{cbu}/users/{idUser}', [SavedAccountController::class, 'destroy']);

        Route::get('/movements', [MovementController::class, 'index']);

        Route::post('/deposits', [DepositController::class, 'store']);

        Route::post('/investments/fixed-term/simulate', [FixedTermController::class, 'simulate']);
    });

    Route::middleware(['auth:api', 'admin'])->get('/admin/test', function () {
        return response()->json([
            'message' => 'Acceso administrativo autorizado.',
        ]);
    });
});
