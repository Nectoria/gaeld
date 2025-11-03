<?php

use App\Models\Company;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Company $company;

    // Basic Information
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $legal_name = '';

    #[Validate('nullable|string|max:255')]
    public string $registration_number = '';

    #[Validate('nullable|string|max:255')]
    public string $vat_number = '';

    // Contact Information
    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    #[Validate('nullable|url|max:255')]
    public string $website = '';

    // Address
    #[Validate('nullable|string|max:255')]
    public string $street = '';

    #[Validate('nullable|string|max:50')]
    public string $street_number = '';

    #[Validate('nullable|string|max:20')]
    public string $postal_code = '';

    #[Validate('nullable|string|max:255')]
    public string $city = '';

    #[Validate('required|string|size:2')]
    public string $country = 'CH';

    // Banking
    #[Validate('nullable|string|max:50')]
    public string $iban = '';

    #[Validate('nullable|string|max:255')]
    public string $bank_name = '';

    // Preferences
    #[Validate('required|string|size:3')]
    public string $currency = 'CHF';

    #[Validate('nullable|string|max:10')]
    public string $locale = '';

    #[Validate('nullable|string|max:50')]
    public string $timezone = '';

    // Logo
    #[Validate('nullable|image|max:2048')]
    public $logo;

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('viewSettings', Company::class);

        $this->company = currentCompany();

        // Load company data
        $this->name = $this->company->name;
        $this->legal_name = $this->company->legal_name ?? '';
        $this->registration_number = $this->company->registration_number ?? '';
        $this->vat_number = $this->company->vat_number ?? '';
        $this->email = $this->company->email ?? '';
        $this->phone = $this->company->phone ?? '';
        $this->website = $this->company->website ?? '';
        $this->street = $this->company->street ?? '';
        $this->street_number = $this->company->street_number ?? '';
        $this->postal_code = $this->company->postal_code ?? '';
        $this->city = $this->company->city ?? '';
        $this->country = $this->company->country ?? 'CH';
        $this->iban = $this->company->iban ?? '';
        $this->bank_name = $this->company->bank_name ?? '';
        $this->currency = $this->company->currency ?? 'CHF';
        $this->locale = $this->company->locale ?? '';
        $this->timezone = $this->company->timezone ?? '';
        $this->is_active = $this->company->is_active ?? true;
    }

    public function save(): void
    {
        $this->authorize('editSettings', Company::class);

        $validated = $this->validate();

        // Handle logo upload
        if ($this->logo) {
            $logoPath = $this->logo->store('logos', 'public');
            $validated['logo'] = $logoPath;

            // Delete old logo if exists
            if ($this->company->logo) {
                \Storage::disk('public')->delete($this->company->logo);
            }
        }

        $this->company->update($validated);

        $this->dispatch('company-settings-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.company-settings-heading')

    <x-settings.company-layout :heading="__('General Information')" :subheading="__('Update your company details and preferences')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <!-- Basic Information -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Basic Information') }}</h3>

                <flux:input wire:model="name" :label="__('Company Name')" type="text" required />
                <flux:input wire:model="legal_name" :label="__('Legal Name')" type="text" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="vat_number" :label="__('VAT Number')" type="text" placeholder="CHE-123.456.789 MWST" />
                    <flux:input wire:model="registration_number" :label="__('Registration Number')" type="text" placeholder="CHE-123.456.789" />
                </div>
            </div>

            <flux:separator />

            <!-- Contact Information -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Contact Information') }}</h3>

                <flux:input wire:model="email" :label="__('Email')" type="email" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="phone" :label="__('Phone')" type="text" placeholder="+41 44 123 45 67" />
                    <flux:input wire:model="website" :label="__('Website')" type="url" placeholder="https://example.com" />
                </div>
            </div>

            <flux:separator />

            <!-- Address -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Address') }}</h3>

                <div class="grid grid-cols-4 gap-4">
                    <div class="col-span-3">
                        <flux:input wire:model="street" :label="__('Street')" type="text" />
                    </div>
                    <flux:input wire:model="street_number" :label="__('Number')" type="text" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="postal_code" :label="__('Postal Code')" type="text" />
                    <div class="col-span-2">
                        <flux:input wire:model="city" :label="__('City')" type="text" />
                    </div>
                </div>

                <flux:input wire:model="country" :label="__('Country Code')" type="text" maxlength="2" required />
            </div>

            <flux:separator />

            <!-- Banking Information -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Banking Information') }}</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Required for generating QR invoices') }}</p>

                <flux:input wire:model="iban" :label="__('IBAN')" type="text" placeholder="CH93 0076 2011 6238 5295 7" />
                <flux:input wire:model="bank_name" :label="__('Bank Name')" type="text" placeholder="UBS Switzerland AG" />
            </div>

            <flux:separator />

            <!-- Preferences -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Preferences') }}</h3>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="currency" :label="__('Currency')" type="text" maxlength="3" required />
                    <flux:input wire:model="locale" :label="__('Locale')" type="text" placeholder="de_CH" />
                    <flux:input wire:model="timezone" :label="__('Timezone')" type="text" placeholder="Europe/Zurich" />
                </div>
            </div>

            <flux:separator />

            <!-- Logo -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Company Logo') }}</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('This logo will appear on invoices and documents') }}</p>

                @if($company->logo)
                    <div class="mb-4">
                        <img src="{{ Storage::url($company->logo) }}" alt="Company Logo" class="w-32 h-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    </div>
                @endif

                <flux:input
                    type="file"
                    wire:model="logo"
                    :label="__('Upload Logo')"
                    accept="image/*"
                />

                @if ($logo)
                    <div class="mt-4">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">{{ __('Preview:') }}</p>
                        <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="w-32 h-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    </div>
                @endif

                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Maximum file size: 2MB. Recommended: PNG or JPG') }}
                </p>
            </div>

            <div class="flex items-center gap-4">
                @can('edit_company_settings')
                    <flux:button variant="primary" type="submit" class="w-full md:w-auto">
                        {{ __('Save Changes') }}
                    </flux:button>
                @endcan

                <x-action-message class="me-3" on="company-settings-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.company-layout>
</section>
