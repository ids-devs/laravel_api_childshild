<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects requests from deactivated dashboard users.
 * Applied globally on all auth:api routes.
 */
class CheckActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account deactivated. Contact your administrator.',
            ], 403);
        }

        return $next($request);
    }
}
