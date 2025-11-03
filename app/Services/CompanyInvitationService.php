<?php

namespace App\Services;

use App\Mail\CompanyInvitationMail;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CompanyInvitationService
{
    /**
     * Create and send a company invitation
     *
     * @throws ValidationException
     */
    public function createInvitation(Company $company, string $email, string $role, int $invitedBy): CompanyInvitation
    {
        // Check if user already belongs to company
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $existingUser->belongsToCompany($company->id)) {
            throw ValidationException::withMessages([
                'email' => 'This user is already a member of this company.',
            ]);
        }

        // Check if there's already a pending invitation
        $existingInvitation = CompanyInvitation::where('company_id', $company->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            throw ValidationException::withMessages([
                'email' => 'There is already a pending invitation for this email.',
            ]);
        }

        // Create invitation
        $invitation = CompanyInvitation::create([
            'company_id' => $company->id,
            'invited_by' => $invitedBy,
            'email' => $email,
            'role' => $role,
            'token' => CompanyInvitation::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        // Refresh model to ensure casts are applied and load relationships
        $invitation->refresh();
        $invitation->load(['company', 'inviter']);

        // Send invitation email
        Mail::to($email)->send(new CompanyInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Cancel a pending invitation
     *
     * @throws \Exception
     */
    public function cancelInvitation(int $invitationId, int $companyId): void
    {
        $invitation = CompanyInvitation::findOrFail($invitationId);

        if ($invitation->company_id !== $companyId) {
            throw new \Exception('Invitation does not belong to this company.');
        }

        $invitation->delete();
    }

    /**
     * Get pending invitations for a company
     */
    public function getPendingInvitations(Company $company)
    {
        return CompanyInvitation::where('company_id', $company->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('inviter')
            ->latest()
            ->get();
    }
}
