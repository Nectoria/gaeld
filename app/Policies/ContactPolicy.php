<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    /**
     * Determine if the user can view any contacts
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_contacts');
    }

    /**
     * Determine if the user can view the contact
     */
    public function view(User $user, Contact $contact): bool
    {
        // User must belong to the same company as the contact
        if (! $user->belongsToCompany($contact->company_id)) {
            return false;
        }

        return $user->can('view_contacts');
    }

    /**
     * Determine if the user can create contacts
     */
    public function create(User $user): bool
    {
        return $user->can('create_contacts');
    }

    /**
     * Determine if the user can update the contact
     */
    public function update(User $user, Contact $contact): bool
    {
        // User must belong to the same company as the contact
        if (! $user->belongsToCompany($contact->company_id)) {
            return false;
        }

        return $user->can('edit_contacts');
    }

    /**
     * Determine if the user can delete the contact
     */
    public function delete(User $user, Contact $contact): bool
    {
        // User must belong to the same company as the contact
        if (! $user->belongsToCompany($contact->company_id)) {
            return false;
        }

        return $user->can('delete_contacts');
    }

    /**
     * Determine if the user can export contacts
     */
    public function export(User $user): bool
    {
        return $user->can('export_contacts');
    }
}
