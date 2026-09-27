<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => 'No tiene permisos para acceder a este recurso.',
            ], 403);
        }

        return $next($request);
    }
}