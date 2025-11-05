<?php

use App\Models\Contact;
use App\Services\ContactService;
use Livewire\Volt\Component;

new class extends Component {
    public Contact $contact;

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

    public function mount(Contact $contact): void
    {
        $this->authorize('update', $contact);

        $this->contact = $contact;

        // Load contact data
        $this->type = $contact->type;
        $this->name = $contact->name;
        $this->contact_person = $contact->contact_person ?? '';
        $this->email = $contact->email ?? '';
        $this->phone = $contact->phone ?? '';
        $this->mobile = $contact->mobile ?? '';
        $this->website = $contact->website ?? '';
        $this->street = $contact->street ?? '';
        $this->street_number = $contact->street_number ?? '';
        $this->postal_code = $contact->postal_code ?? '';
        $this->city = $contact->city ?? '';
        $this->country = $contact->country ?? 'CH';
        $this->vat_number = $contact->vat_number ?? '';
        $this->tax_id = $contact->tax_id ?? '';
        $this->iban = $contact->iban ?? '';
        $this->bank_name = $contact->bank_name ?? '';
        $this->payment_term_days = $contact->payment_term_days ?? 30;
        $this->currency = $contact->currency ?? 'CHF';
        $this->reference_number = $contact->reference_number ?? '';
        $this->notes = $contact->notes ?? '';
        $this->is_active = $contact->is_active ?? true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->contact);

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

        $contact = app(ContactService::class)->updateContact(
            $this->contact,
            $validated
        );

        $this->redirect(route('contacts.index'), navigate: true);
    }
}; ?>

<div>
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('contacts.index') }}" class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 mb-2 inline-flex items-center" wire:navigate>
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        {{ __('Back to Contacts') }}
                    </a>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ __('Edit Contact') }}</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $contact->name }}
                    </p>
                </div>
                <flux:button href="{{ route('contacts.index') }}" variant="ghost" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>

        <form wire:submit="update">
            <x-contacts.form-fields :submitText="__('Update Contact')" />
        </form>
</div>
