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
     * Updates both session (for current request) and database (for persistence)
     */
    public function switch(int $companyId): bool
    {
        $user = auth()->user();
        $company = Company::find($companyId);

        if (! $company || ! $this->userHasAccess($user, $company)) {
            return false;
        }

        // Update session for immediate use
        Session::put(self::SESSION_KEY, $companyId);

        // Persist to database for next login
        if ($user) {
            $user->current_company_id = $companyId;
            $user->save();
        }

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
     * Loads from database field if available, otherwise uses first accessible company
     */
    private function getDefaultCompany(): ?Company
    {
        $user = auth()->user();

        // First, try to load from user's saved preference
        if ($user && $user->current_company_id) {
            $company = Company::find($user->current_company_id);

            // Verify user still has access
            if ($company && $this->userHasAccess($user, $company)) {
                Session::put(self::SESSION_KEY, $company->id);

                return $company;
            }
        }

        // Otherwise, get first accessible company
        $company = $this->accessible()->first();

        if ($company) {
            Session::put(self::SESSION_KEY, $company->id);

            // Persist for next time
            if ($user) {
                $user->current_company_id = $company->id;
                $user->save();
            }
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
