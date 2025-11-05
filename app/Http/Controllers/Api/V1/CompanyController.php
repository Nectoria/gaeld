<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Models\Company;
use App\Services\TenantService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * List all accessible companies for the authenticated user
     */
    public function index(Request $request)
    {
        $companies = $this->tenantService->accessible($request->user());

        return CompanyResource::collection($companies);
    }

    /**
     * Get a specific company's details
     */
    public function show(Request $request, Company $company)
    {
        // Ensure user has access
        if (! $this->tenantService->userHasAccess($request->user(), $company)) {
            return response()->json([
                'error' => [
                    'message' => 'Access denied to this company',
                    'code' => 'PERMISSION_DENIED',
                    'status' => 403,
                ],
            ], 403);
        }

        return new CompanyResource($company);
    }

    /**
     * Update company settings
     */
    public function update(Request $request, Company $company)
    {
        // Check if user has owner or admin role
        $role = $request->user()->roleInCompany($company->id);

        if (! in_array($role, ['owner', 'admin'])) {
            return response()->json([
                'error' => [
                    'message' => 'Insufficient permissions to update company',
                    'code' => 'PERMISSION_DENIED',
                    'status' => 403,
                ],
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'legal_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'website' => 'sometimes|nullable|url|max:255',
            'brand_color' => 'sometimes|nullable|string|max:7',
        ]);

        $company->update($validated);

        return new CompanyResource($company->fresh());
    }

    /**
     * Delete a company (soft delete)
     */
    public function destroy(Request $request, Company $company)
    {
        // Only owner can delete
        $role = $request->user()->roleInCompany($company->id);

        if ($role !== 'owner') {
            return response()->json([
                'error' => [
                    'message' => 'Only company owners can delete companies',
                    'code' => 'PERMISSION_DENIED',
                    'status' => 403,
                ],
            ], 403);
        }

        $company->delete();

        return response()->noContent();
    }
}
