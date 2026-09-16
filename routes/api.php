<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\ProfileController;

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

        Route::post('/transfers', [TransferController::class, 'store']);

    });

});