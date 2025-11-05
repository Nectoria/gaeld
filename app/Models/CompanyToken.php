<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

class CompanyToken extends PersonalAccessToken
{
    /**
     * Abilities for API tokens
     */
    public const ABILITIES = [
        // Invoice permissions
        'invoices:read' => 'View invoices',
        'invoices:create' => 'Create invoices',
        'invoices:update' => 'Update invoices',
        'invoices:delete' => 'Delete invoices',

        // Contact permissions
        'contacts:read' => 'View contacts',
        'contacts:create' => 'Create contacts',
        'contacts:update' => 'Update contacts',
        'contacts:delete' => 'Delete contacts',

        // Company permissions
        'company:read' => 'View company details',
        'company:update' => 'Update company details',

        // Admin permission (all abilities)
        '*' => 'Full access',
    ];

    /**
     * Get all available abilities
     */
    public static function availableAbilities(): array
    {
        return array_keys(self::ABILITIES);
    }

    /**
     * Get abilities with descriptions
     */
    public static function abilitiesWithDescriptions(): array
    {
        return self::ABILITIES;
    }

    /**
     * Scope query to specific company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Check if token has specific ability
     */
    public function hasAbility(string $ability): bool
    {
        // Full access token
        if ($this->can('*')) {
            return true;
        }

        return $this->can($ability);
    }

    /**
     * Check if token belongs to company
     */
    public function belongsToCompany(int $companyId): bool
    {
        return $this->company_id === $companyId;
    }

    /**
     * Get the company this token belongs to
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
