<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ContactService
{
    /**
     * Create a new contact
     */
    public function createContact(Company $company, array $data): Contact
    {
        return DB::transaction(function () use ($company, $data) {
            return Contact::create([
                'company_id' => $company->id,
                'type' => $data['type'] ?? 'customer',
                'name' => $data['name'],
                'contact_person' => $data['contact_person'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'website' => $data['website'] ?? null,
                'vat_number' => $data['vat_number'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'street' => $data['street'] ?? null,
                'street_number' => $data['street_number'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'CH',
                'iban' => $data['iban'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'payment_term_days' => $data['payment_term_days'] ?? 30,
                'currency' => $data['currency'] ?? 'CHF',
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Update an existing contact
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            $contact->update([
                'type' => $data['type'] ?? $contact->type,
                'name' => $data['name'],
                'contact_person' => $data['contact_person'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'website' => $data['website'] ?? null,
                'vat_number' => $data['vat_number'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'street' => $data['street'] ?? null,
                'street_number' => $data['street_number'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'CH',
                'iban' => $data['iban'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'payment_term_days' => $data['payment_term_days'] ?? 30,
                'currency' => $data['currency'] ?? 'CHF',
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $contact->refresh();
        });
    }

    /**
     * Delete a contact (soft delete)
     */
    public function deleteContact(Contact $contact): bool
    {
        return DB::transaction(function () use ($contact) {
            // Check if contact has invoices
            if ($contact->invoices()->count() > 0) {
                // Instead of deleting, mark as inactive
                $contact->update(['is_active' => false]);

                return true;
            }

            return $contact->delete();
        });
    }
}
