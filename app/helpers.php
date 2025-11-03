<?php

use App\Services\TenantService;
use App\Models\Company;

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
