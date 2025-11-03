<?php

namespace App\Traits;

use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    /**
     * Boot the trait and add global scope
     */
    protected static function bootBelongsToTenant(): void
    {
        // Auto-scope all queries to current company
        static::addGlobalScope('company', function (Builder $builder) {
            $tenantService = app(TenantService::class);
            $companyId = $tenantService->currentId();

            if ($companyId) {
                $builder->where($builder->getModel()->getTable().'.company_id', $companyId);
            }
        });

        // Auto-set company_id on creation
        static::creating(function ($model) {
            if (! $model->company_id) {
                $tenantService = app(TenantService::class);
                $model->company_id = $tenantService->currentId();
            }
        });
    }

    /**
     * Scope to specific company
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->withoutGlobalScope('company')->where('company_id', $companyId);
    }

    /**
     * Get all records without company scoping
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }

    /**
     * Scope to all companies user has access to
     */
    public function scopeAllAccessible(Builder $query): Builder
    {
        $tenantService = app(TenantService::class);
        $companyIds = $tenantService->accessible()->pluck('id');

        return $query->withoutGlobalScope('company')->whereIn('company_id', $companyIds);
    }
}
