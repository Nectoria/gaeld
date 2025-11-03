<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for onboarding routes
        if ($request->routeIs('onboarding.*')) {
            return $next($request);
        }

        // Check if user has any accessible companies
        if (tenant()->accessible()->count() === 0) {
            return redirect()->route('onboarding.create-company');
        }

        return $next($request);
    }
}
