<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Wallet API',
    description: 'Documentación de la API de Wallet.'
)]

#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]

#[OA\Schema(
    schema: 'Account',
    required: ['cbu', 'balance', 'type', 'currency'],
    properties: [
        new OA\Property(
            property: 'cbu',
            type: 'string',
            example: '0000000000000000000001'
        ),
        new OA\Property(
            property: 'balance',
            type: 'string',
            example: '0.00'
        ),
        new OA\Property(
            property: 'type',
            type: 'string',
            example: 'savings'
        ),
        new OA\Property(
            property: 'currency',
            type: 'string',
            example:'ARS'
        )
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'UnauthorizedError',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'No autorizado.'
        )
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'AccountNotFoundError',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'El usuario no posee una cuenta asociada.'
        )
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'ValidationError',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Los datos proporcionados no son válidos.'
        ),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['amount' => ['El campo amount es obligatorio.']]
        )
    ],
    type: 'object'
)]

abstract class Controller
{
    //
}
