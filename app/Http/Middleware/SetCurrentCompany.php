<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentCompany
{
    /**
     * Handle an incoming request.
     *
     * This middleware ensures the session has a current company ID.
     * If session is empty but user has a default company in database,
     * it loads that into the session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // If session doesn't have current company, but user has one in database, load it
        if (! Session::has('current_company_id') && $user->current_company_id) {
            $tenantService = app(TenantService::class);

            // Verify user still has access to this company
            if ($tenantService->userHasAccess($user, $user->currentCompany)) {
                Session::put('current_company_id', $user->current_company_id);
            }
        }

        return $next($request);
    }
}
