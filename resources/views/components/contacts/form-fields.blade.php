@props(['submitText' => __('Save Contact')])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Basic Information -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ __('Basic Information') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Type -->
                <div>
                    <flux:select
                        wire:model="type"
                        :label="__('Type')"
                        required
                    >
                        <option value="customer">{{ __('Customer') }}</option>
                        <option value="vendor">{{ __('Vendor') }}</option>
                        <option value="both">{{ __('Both') }}</option>
                    </flux:select>
                </div>

                <!-- Status -->
                <div class="flex items-center pt-8">
                    <flux:checkbox
                        wire:model="is_active"
                        :label="__('Active')"
                    />
                </div>

                <!-- Name -->
                <div class="md:col-span-2">
                    <flux:input
                        wire:model="name"
                        type="text"
                        :label="__('Company Name')"
                        placeholder="ACME Corp AG"
                        required
                    />
                </div>

                <!-- Contact Person -->
                <div class="md:col-span-2">
                    <flux:input
                        wire:model="contact_person"
                        type="text"
                        :label="__('Contact Person')"
                        placeholder="John Doe"
                    />
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ __('Contact Information') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Email -->
                <div class="md:col-span-2">
                    <flux:input
                        wire:model="email"
                        type="email"
                        :label="__('Email')"
                        placeholder="contact@example.com"
                    />
                </div>

                <!-- Phone -->
                <div>
                    <flux:input
                        wire:model="phone"
                        type="text"
                        :label="__('Phone')"
                        placeholder="+41 44 123 45 67"
                    />
                </div>

                <!-- Mobile -->
                <div>
                    <flux:input
                        wire:model="mobile"
                        type="text"
                        :label="__('Mobile')"
                        placeholder="+41 79 123 45 67"
                    />
                </div>

                <!-- Website -->
                <div class="md:col-span-2">
                    <flux:input
                        wire:model="website"
                        type="url"
                        :label="__('Website')"
                        placeholder="https://example.com"
                    />
                </div>
            </div>
        </div>

        <!-- Address -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ __('Address') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Street -->
                <div class="md:col-span-3">
                    <flux:input
                        wire:model="street"
                        type="text"
                        :label="__('Street')"
                        placeholder="Bahnhofstrasse"
                    />
                </div>

                <!-- Street Number -->
                <div>
                    <flux:input
                        wire:model="street_number"
                        type="text"
                        :label="__('Number')"
                        placeholder="123"
                    />
                </div>

                <!-- Postal Code -->
                <div>
                    <flux:input
                        wire:model="postal_code"
                        type="text"
                        :label="__('Postal Code')"
                        placeholder="8001"
                    />
                </div>

                <!-- City -->
                <div class="md:col-span-2">
                    <flux:input
                        wire:model="city"
                        type="text"
                        :label="__('City')"
                        placeholder="Zürich"
                    />
                </div>

                <!-- Country -->
                <div>
                    <flux:input
                        wire:model="country"
                        type="text"
                        :label="__('Country')"
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
                {{ __('Tax & Legal') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- VAT Number -->
                <div>
                    <flux:input
                        wire:model="vat_number"
                        type="text"
                        :label="__('VAT Number')"
                        placeholder="CHE-123.456.789 MWST"
                    />
                </div>

                <!-- Tax ID -->
                <div>
                    <flux:input
                        wire:model="tax_id"
                        type="text"
                        :label="__('Tax ID')"
                        placeholder="Optional tax identifier"
                    />
                </div>
            </div>
        </div>

        <!-- Banking -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ __('Banking Information') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- IBAN -->
                <div class="md:col-span-2">
                    <flux:input
                        wire:model="iban"
                        type="text"
                        :label="__('IBAN')"
                        placeholder="CH93 0076 2011 6238 5295 7"
                    />
                </div>

                <!-- Bank Name -->
                <div class="md:col-span-2">
                    <flux:input
                        wire:model="bank_name"
                        type="text"
                        :label="__('Bank Name')"
                        placeholder="UBS Switzerland AG"
                    />
                </div>
            </div>
        </div>

        <!-- Business Terms -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ __('Business Terms') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Payment Terms -->
                <div>
                    <flux:input
                        wire:model="payment_term_days"
                        type="number"
                        :label="__('Payment Terms (days)')"
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
                        :label="__('Currency')"
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
                        :label="__('Reference Number')"
                        placeholder="Optional reference"
                    />
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ __('Notes') }}
            </h2>

            <flux:textarea
                wire:model="notes"
                placeholder="{{ __('Internal notes about this contact...') }}"
                rows="4"
            />
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Actions -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ __('Actions') }}
            </h2>

            <div class="space-y-3">
                <flux:button
                    type="submit"
                    variant="primary"
                    class="w-full"
                >
                    {{ $submitText }}
                </flux:button>

                <flux:button
                    href="{{ route('contacts.index') }}"
                    variant="ghost"
                    class="w-full"
                    wire:navigate
                >
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>

        <!-- Help Text -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
                {{ __('Quick Tips') }}
            </h3>
            <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1 list-disc list-inside">
                <li>{{ __('Only company name is required') }}</li>
                <li>{{ __('Add payment terms for automatic invoice due dates') }}</li>
                <li>{{ __('VAT number is needed for invoicing') }}</li>
                <li>{{ __('Customer contacts appear in invoice creation') }}</li>
            </ul>
        </div>
    </div>
</div>
