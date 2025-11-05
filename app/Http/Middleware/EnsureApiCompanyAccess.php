<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiCompanyAccess
{
    /**
     * Ensure the user has access to the company in the URL parameter
     */
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->route('company');

        if ($companyId) {
            $tenantService = app(TenantService::class);

            // Check if user has access to this company
            if (! $tenantService->accessible()->contains('id', (int) $companyId)) {
                return response()->json([
                    'error' => [
                        'message' => 'Access denied to this company',
                        'code' => 'TENANT_VIOLATION',
                        'status' => 403,
                    ],
                ], 403);
            }

            // Verify token's company_id matches the URL company (if token has company_id)
            if ($request->user() && $request->bearerToken()) {
                $token = $request->user()->currentAccessToken();

                if ($token && isset($token->company_id) && $token->company_id != $companyId) {
                    return response()->json([
                        'error' => [
                            'message' => 'Token is not authorized for this company',
                            'code' => 'TENANT_VIOLATION',
                            'status' => 403,
                        ],
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
