<?php

use App\Models\Company;
use App\Services\TenantService;

if (! function_exists('tenant')) {
    /**
     * Get the tenant service instance or current company
     */
    function tenant(?int $companyId = null): TenantService|Company|null
    {
        $service = app(TenantService::class);

        if ($companyId !== null) {
            return Company::find($companyId);
        }

        return $service;
    }
}

if (! function_exists('currentCompany')) {
    /**
     * Get the current company
     */
    function currentCompany(): ?Company
    {
        return app(TenantService::class)->current();
    }
}
