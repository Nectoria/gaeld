<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine if the user can view company settings
     */
    public function viewSettings(User $user): bool
    {
        return $user->can('view_company_settings');
    }

    /**
     * Determine if the user can edit company settings
     */
    public function editSettings(User $user): bool
    {
        return $user->can('edit_company_settings');
    }

    /**
     * Determine if the user can manage company users
     */
    public function manageUsers(User $user): bool
    {
        return $user->can('manage_company_users');
    }

    /**
     * Determine if the user can view the company
     */
    public function view(User $user, Company $company): bool
    {
        return $user->belongsToCompany($company->id);
    }

    /**
     * Determine if the user can update the company
     */
    public function update(User $user, Company $company): bool
    {
        if (! $user->belongsToCompany($company->id)) {
            return false;
        }

        return $user->can('edit_settings');
    }
}
