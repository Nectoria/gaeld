<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyFromToken
{
    /**
     * Handle an incoming API request and set company context from token
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process for API requests with Sanctum token
        if ($request->user() && $request->bearerToken()) {
            $token = $request->user()->currentAccessToken();

            if ($token && $token->company_id) {
                // Verify user still has access to this company
                if (! $request->user()->belongsToCompany($token->company_id)) {
                    return response()->json([
                        'error' => [
                            'message' => 'Token company access revoked',
                            'code' => 'TOKEN_INVALID',
                            'status' => 401,
                        ],
                    ], 401);
                }

                // Set company context for multi-tenant queries
                Session::put('current_company_id', $token->company_id);
            }
        }

        return $next($request);
    }
}
