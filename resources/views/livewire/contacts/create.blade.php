<?php

use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    // Basic Information
    public string $type = 'customer';
    public string $name = '';
    public string $contact_person = '';

    // Contact Information
    public string $email = '';
    public string $phone = '';
    public string $mobile = '';
    public string $website = '';

    // Address
    public string $street = '';
    public string $street_number = '';
    public string $postal_code = '';
    public string $city = '';
    public string $country = 'CH';

    // Tax & Legal
    public string $vat_number = '';
    public string $tax_id = '';

    // Banking
    public string $iban = '';
    public string $bank_name = '';

    // Business Terms
    public int $payment_term_days = 30;
    public string $currency = 'CHF';
    public string $reference_number = '';

    // Notes
    public string $notes = '';
    public bool $is_active = true;

    public function save(): void
    {
        $this->authorize('create', Contact::class);

        $validated = $this->validate([
            'type' => 'required|in:customer,vendor,both',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'required|string|size:2',
            'vat_number' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'payment_term_days' => 'required|integer|min:0|max:365',
            'currency' => 'required|string|size:3',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $contact = app(ContactService::class)->createContact(
            currentCompany(),
            $validated
        );

        $this->redirect(route('contacts.index'), navigate: true);
    }
}; ?>

<x-page-layout>
    <x-page-header
        :title="__('Create Contact')"
        :description="__('Add a new customer or vendor')"
        :back-href="route('contacts.index')"
        :back-label="__('Back to Contacts')"
    >
        <x-slot:action>
            <flux:button href="{{ route('contacts.index') }}" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </x-slot:action>
    </x-page-header>

    <form wire:submit="save">
        <x-contacts.form-fields :submitText="__('Create Contact')" />
    </form>
</x-page-layout>
