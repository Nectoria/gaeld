<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_invoices') || $user->can('view_own_invoices');
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Must belong to the same company
        if (! $user->belongsToCompany($invoice->company_id)) {
            return false;
        }

        // Check if user has general view permission
        if ($user->can('view_invoices')) {
            return true;
        }

        // Check if user can view their own invoices
        if ($user->can('view_own_invoices') && $invoice->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->can('create_invoices');
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Must belong to the same company
        if (! $user->belongsToCompany($invoice->company_id)) {
            return false;
        }

        // Can't edit paid or cancelled invoices
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return false;
        }

        // Check if user has general edit permission
        if ($user->can('edit_invoices')) {
            return true;
        }

        // Check if user can edit their own invoices
        if ($user->can('edit_own_invoices') && $invoice->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Must belong to the same company
        if (! $user->belongsToCompany($invoice->company_id)) {
            return false;
        }

        // Can't delete paid invoices
        if ($invoice->status === 'paid') {
            return false;
        }

        // Check if user has general delete permission
        if ($user->can('delete_invoices')) {
            return true;
        }

        // Check if user can delete their own invoices (only drafts)
        if ($user->can('delete_own_invoices')
            && $invoice->created_by === $user->id
            && $invoice->status === 'draft') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can send the invoice.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        // Must belong to the same company
        if (! $user->belongsToCompany($invoice->company_id)) {
            return false;
        }

        // Can only send draft invoices
        if ($invoice->status !== 'draft') {
            return false;
        }

        return $user->can('send_invoices');
    }

    /**
     * Determine whether the user can mark the invoice as paid.
     */
    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        // Must belong to the same company
        if (! $user->belongsToCompany($invoice->company_id)) {
            return false;
        }

        // Can't mark already paid invoices
        if ($invoice->status === 'paid') {
            return false;
        }

        return $user->can('mark_invoices_paid');
    }

    /**
     * Determine whether the user can approve the invoice.
     */
    public function approve(User $user, Invoice $invoice): bool
    {
        // Must belong to the same company
        if (! $user->belongsToCompany($invoice->company_id)) {
            return false;
        }

        // Can only approve sent invoices
        if (! in_array($invoice->status, ['sent', 'viewed'])) {
            return false;
        }

        return $user->can('approve_invoices');
    }

    /**
     * Determine whether the user can generate QR invoice.
     */
    public function generateQr(User $user, Invoice $invoice): bool
    {
        // Must belong to the same company
        if (! $user->belongsToCompany($invoice->company_id)) {
            return false;
        }

        return $user->can('generate_qr_invoices');
    }

    /**
     * Determine whether the user can export invoices.
     */
    public function export(User $user): bool
    {
        return $user->can('export_invoices');
    }

    /**
     * Determine whether the user can restore the invoice.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->belongsToCompany($invoice->company_id)
            && $user->can('delete_invoices');
    }

    /**
     * Determine whether the user can permanently delete the invoice.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->belongsToCompany($invoice->company_id)
            && $user->can('delete_invoices');
    }
}
