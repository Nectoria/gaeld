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

<div>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('contacts.index') }}" class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 mb-2 inline-flex items-center" wire:navigate>
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Contacts
                    </a>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">Create Contact</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Add a new customer or vendor
                    </p>
                </div>
                <flux:button href="{{ route('contacts.index') }}" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
            </div>
        </div>

        <form wire:submit="save">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Basic Information
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Type -->
                            <div>
                                <flux:select
                                    wire:model="type"
                                    label="Type"
                                    required
                                >
                                    <option value="customer">Customer</option>
                                    <option value="vendor">Vendor</option>
                                    <option value="both">Both</option>
                                </flux:select>
                            </div>

                            <!-- Status -->
                            <div class="flex items-center pt-8">
                                <flux:checkbox
                                    wire:model="is_active"
                                    label="Active"
                                />
                            </div>

                            <!-- Name -->
                            <div class="md:col-span-2">
                                <flux:input
                                    wire:model="name"
                                    type="text"
                                    label="Company Name"
                                    placeholder="ACME Corp AG"
                                    required
                                />
                            </div>

                            <!-- Contact Person -->
                            <div class="md:col-span-2">
                                <flux:input
                                    wire:model="contact_person"
                                    type="text"
                                    label="Contact Person"
                                    placeholder="John Doe"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Contact Information
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Email -->
                            <div class="md:col-span-2">
                                <flux:input
                                    wire:model="email"
                                    type="email"
                                    label="Email"
                                    placeholder="contact@example.com"
                                />
                            </div>

                            <!-- Phone -->
                            <div>
                                <flux:input
                                    wire:model="phone"
                                    type="text"
                                    label="Phone"
                                    placeholder="+41 44 123 45 67"
                                />
                            </div>

                            <!-- Mobile -->
                            <div>
                                <flux:input
                                    wire:model="mobile"
                                    type="text"
                                    label="Mobile"
                                    placeholder="+41 79 123 45 67"
                                />
                            </div>

                            <!-- Website -->
                            <div class="md:col-span-2">
                                <flux:input
                                    wire:model="website"
                                    type="url"
                                    label="Website"
                                    placeholder="https://example.com"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Address
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Street -->
                            <div class="md:col-span-3">
                                <flux:input
                                    wire:model="street"
                                    type="text"
                                    label="Street"
                                    placeholder="Bahnhofstrasse"
                                />
                            </div>

                            <!-- Street Number -->
                            <div>
                                <flux:input
                                    wire:model="street_number"
                                    type="text"
                                    label="Number"
                                    placeholder="123"
                                />
                            </div>

                            <!-- Postal Code -->
                            <div>
                                <flux:input
                                    wire:model="postal_code"
                                    type="text"
                                    label="Postal Code"
                                    placeholder="8001"
                                />
                            </div>

                            <!-- City -->
                            <div class="md:col-span-2">
                                <flux:input
                                    wire:model="city"
                                    type="text"
                                    label="City"
                                    placeholder="Zürich"
                                />
                            </div>

                            <!-- Country -->
                            <div>
                                <flux:input
                                    wire:model="country"
                                    type="text"
                                    label="Country"
                                    placeholder="CH"
                                    maxlength="2"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Tax & Legal -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Tax & Legal
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- VAT Number -->
                            <div>
                                <flux:input
                                    wire:model="vat_number"
                                    type="text"
                                    label="VAT Number"
                                    placeholder="CHE-123.456.789 MWST"
                                />
                            </div>

                            <!-- Tax ID -->
                            <div>
                                <flux:input
                                    wire:model="tax_id"
                                    type="text"
                                    label="Tax ID"
                                    placeholder="Optional tax identifier"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Banking -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Banking Information
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- IBAN -->
                            <div class="md:col-span-2">
                                <flux:input
                                    wire:model="iban"
                                    type="text"
                                    label="IBAN"
                                    placeholder="CH93 0076 2011 6238 5295 7"
                                />
                            </div>

                            <!-- Bank Name -->
                            <div class="md:col-span-2">
                                <flux:input
                                    wire:model="bank_name"
                                    type="text"
                                    label="Bank Name"
                                    placeholder="UBS Switzerland AG"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Business Terms -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Business Terms
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Payment Terms -->
                            <div>
                                <flux:input
                                    wire:model="payment_term_days"
                                    type="number"
                                    label="Payment Terms (days)"
                                    min="0"
                                    max="365"
                                    required
                                />
                            </div>

                            <!-- Currency -->
                            <div>
                                <flux:input
                                    wire:model="currency"
                                    type="text"
                                    label="Currency"
                                    placeholder="CHF"
                                    maxlength="3"
                                    required
                                />
                            </div>

                            <!-- Reference Number -->
                            <div>
                                <flux:input
                                    wire:model="reference_number"
                                    type="text"
                                    label="Reference Number"
                                    placeholder="Optional reference"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Notes
                        </h2>

                        <flux:textarea
                            wire:model="notes"
                            placeholder="Internal notes about this contact..."
                            rows="4"
                        />
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Actions -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Actions
                        </h2>

                        <div class="space-y-3">
                            <flux:button
                                type="submit"
                                variant="primary"
                                class="w-full"
                            >
                                Create Contact
                            </flux:button>

                            <flux:button
                                href="{{ route('contacts.index') }}"
                                variant="ghost"
                                class="w-full"
                                wire:navigate
                            >
                                Cancel
                            </flux:button>
                        </div>
                    </div>

                    <!-- Help Text -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
                            Quick Tips
                        </h3>
                        <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1 list-disc list-inside">
                            <li>Only company name is required</li>
                            <li>Add payment terms for automatic invoice due dates</li>
                            <li>VAT number is needed for invoicing</li>
                            <li>Customer contacts appear in invoice creation</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
