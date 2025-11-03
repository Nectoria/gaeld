<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyUserService
{
    /**
     * Remove a user from a company
     *
     * @throws ValidationException
     */
    public function removeUser(Company $company, int $userId, int $currentUserId): void
    {
        // Can't remove yourself
        if ($userId === $currentUserId) {
            throw ValidationException::withMessages([
                'remove' => 'You cannot remove yourself from the company.',
            ]);
        }

        $user = User::findOrFail($userId);
        $pivot = $user->companies()->where('company_id', $company->id)->first()?->pivot;

        // Can't remove if it's the last owner
        if ($pivot && $pivot->isOwner()) {
            $ownersCount = $company->users()->wherePivot('role', 'owner')->count();
            if ($ownersCount <= 1) {
                throw ValidationException::withMessages([
                    'remove' => 'Cannot remove the last owner of the company.',
                ]);
            }
        }

        $company->users()->detach($userId);
    }

    /**
     * Update a user's role in the company
     *
     * @throws ValidationException
     */
    public function updateUserRole(Company $company, int $userId, string $newRole, int $currentUserId): void
    {
        $user = User::findOrFail($userId);

        // Can't change your own role
        if ($user->id === $currentUserId) {
            throw ValidationException::withMessages([
                'updateRole' => 'You cannot change your own role.',
            ]);
        }

        DB::transaction(function () use ($company, $user, $newRole) {
            // Update role in pivot table
            $company->users()->updateExistingPivot($user->id, [
                'role' => $newRole,
            ]);

            // Update Spatie role
            $user->syncRoles([$newRole]);
        });
    }

    /**
     * Transfer ownership from current owner to another user
     *
     * @throws \Exception
     */
    public function transferOwnership(Company $company, User $currentOwner, int $newOwnerId): void
    {
        $newOwner = User::findOrFail($newOwnerId);

        // Verify current user is owner
        $currentUserPivot = $currentOwner->companies()->where('company_id', $company->id)->first()?->pivot;
        if (! $currentUserPivot || ! $currentUserPivot->isOwner()) {
            throw new \Exception('Only owners can transfer ownership.');
        }

        DB::transaction(function () use ($company, $currentOwner, $newOwner) {
            // Change current owner to admin
            $company->users()->updateExistingPivot($currentOwner->id, [
                'role' => 'admin',
            ]);

            // Make selected user the new owner
            $company->users()->updateExistingPivot($newOwner->id, [
                'role' => 'owner',
            ]);

            // Update Spatie roles
            $currentOwner->syncRoles(['admin']);
            $newOwner->syncRoles(['owner']);
        });
    }
}
