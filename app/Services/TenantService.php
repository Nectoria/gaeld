<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class TenantService
{
    private const SESSION_KEY = 'current_company_id';

    private const CACHE_PREFIX = 'user_companies_';

    /**
     * Get the current company for the authenticated user
     */
    public function current(): ?Company
    {
        $companyId = Session::get(self::SESSION_KEY);

        if (! $companyId) {
            return $this->getDefaultCompany();
        }

        $company = Company::find($companyId);

        // If company doesn't exist or user doesn't have access, get default
        if (! $company || ! $this->userHasAccess(auth()->user(), $company)) {
            return $this->getDefaultCompany();
        }

        return $company;
    }

    /**
     * Get the current company ID
     */
    public function currentId(): ?int
    {
        return $this->current()?->id;
    }

    /**
     * Switch to a different company
     */
    public function switch(int $companyId): bool
    {
        $company = Company::find($companyId);

        if (! $company || ! $this->userHasAccess(auth()->user(), $company)) {
            return false;
        }

        Session::put(self::SESSION_KEY, $companyId);

        return true;
    }

    /**
     * Get all companies accessible by the user
     */
    public function accessible(?User $user = null): \Illuminate\Support\Collection
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return collect();
        }

        return Cache::remember(
            self::CACHE_PREFIX.$user->id,
            now()->addMinutes(60),
            fn () => $user->companies()
                ->where('companies.is_active', true)
                ->wherePivot('is_active', true)
                ->get()
        );
    }

    /**
     * Check if user has access to a company
     */
    public function userHasAccess(?User $user, Company $company): bool
    {
        if (! $user) {
            return false;
        }

        return $this->accessible($user)->contains('id', $company->id);
    }

    /**
     * Get the default company (first active company for user)
     */
    private function getDefaultCompany(): ?Company
    {
        $company = $this->accessible()->first();

        if ($company) {
            Session::put(self::SESSION_KEY, $company->id);
        }

        return $company;
    }

    /**
     * Forget the current company (useful on logout)
     */
    public function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Clear user companies cache
     */
    public function clearCache(?User $user = null): void
    {
        $user = $user ?? auth()->user();

        if ($user) {
            Cache::forget(self::CACHE_PREFIX.$user->id);
        }
    }

    /**
     * Scope query to current company
     */
    public function scopeQuery($query)
    {
        $companyId = $this->currentId();

        if (! $companyId) {
            // Return empty result if no company context
            return $query->whereRaw('1 = 0');
        }

        return $query->where('company_id', $companyId);
    }
}
