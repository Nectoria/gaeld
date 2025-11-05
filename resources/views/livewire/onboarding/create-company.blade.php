<?php

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $legal_name = '';

    #[Validate('nullable|string|max:255')]
    public string $vat_number = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

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

    #[Validate('required|string|size:3')]
    public string $currency = 'CHF';

    public function mount(): void
    {
        // Check if user already has a company
        if (tenant()->accessible()->count() > 0) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function createCompany(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            // Create the company
            $company = Company::create([
                ...$validated,
                'is_active' => true,
            ]);

            // Attach the user as owner
            $company->users()->attach(Auth::id(), [
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);

            // Assign admin role to user if not already assigned
            if (!Auth::user()->hasRole('admin')) {
                Auth::user()->assignRole('admin');
            }

            // Set as current company
            tenant()->switch($company->id);

            // Clear cache
            tenant()->clearCache(Auth::user());
        });

        session()->flash('success', __('Company created successfully!'));
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <x-app-logo class="mx-auto h-12 w-auto" />
            <h1 class="mt-6 text-3xl font-bold text-zinc-900 dark:text-white">
                {{ __('Welcome to Gäld') }}
            </h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __("Let's set up your company to get started") }}
            </p>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 text-white font-semibold">
                        1
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Company Setup') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Basic information') }}</p>
                    </div>
                </div>
                <div class="w-16 h-0.5 bg-zinc-300 dark:bg-zinc-700 mx-4"></div>
                <div class="flex items-center opacity-50">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-zinc-300 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 font-semibold">
                        2
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Invite Team') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Coming next') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form wire:submit="createCompany">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-lg p-8 space-y-6">
                <!-- Basic Information -->
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                        {{ __('Basic Information') }}
                    </h2>

                    <div class="space-y-4">
                        <!-- Company Name -->
                        <flux:input
                            wire:model="name"
                            type="text"
                            :label="__('Company Name')"
                            placeholder="ACME Corp AG"
                            required
                            autofocus
                        />

                        <!-- Legal Name -->
                        <flux:input
                            wire:model="legal_name"
                            type="text"
                            :label="__('Legal Name (optional)')"
                            placeholder="ACME Corporation AG"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <!-- VAT Number -->
                            <flux:input
                                wire:model="vat_number"
                                type="text"
                                :label="__('VAT Number (optional)')"
                                placeholder="CHE-123.456.789"
                            />

                            <!-- Email -->
                            <flux:input
                                wire:model="email"
                                type="email"
                                :label="__('Company Email (optional)')"
                                placeholder="info@acme.com"
                            />
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                        {{ __('Address (optional)') }}
                    </h2>

                    <div class="space-y-4">
                        <div class="grid grid-cols-4 gap-4">
                            <div class="col-span-3">
                                <flux:input
                                    wire:model="street"
                                    type="text"
                                    :label="__('Street')"
                                    placeholder="Hauptstrasse"
                                />
                            </div>
                            <flux:input
                                wire:model="street_number"
                                type="text"
                                :label="__('Number')"
                                placeholder="123"
                            />
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <flux:input
                                wire:model="postal_code"
                                type="text"
                                :label="__('Postal Code')"
                                placeholder="8000"
                            />
                            <div class="col-span-2">
                                <flux:input
                                    wire:model="city"
                                    type="text"
                                    :label="__('City')"
                                    placeholder="Zürich"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preferences -->
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                        {{ __('Preferences') }}
                    </h2>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input
                            wire:model="country"
                            type="text"
                            :label="__('Country Code')"
                            placeholder="CH"
                            maxlength="2"
                            required
                        />
                        <flux:input
                            wire:model="currency"
                            type="text"
                            :label="__('Currency')"
                            placeholder="CHF"
                            maxlength="3"
                            required
                        />
                    </div>
                </div>

                <!-- Info Box -->
                <x-alert type="info" :title="__('Don\'t worry!')">
                    {{ __("You can update all of this information later in Company Settings. Let's just get you started with the basics.") }}
                </x-alert>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <flux:button
                        type="submit"
                        variant="primary"
                        class="px-8"
                    >
                        {{ __('Create Company & Continue') }}
                    </flux:button>
                </div>
            </div>
        </form>

        <!-- Help Text -->
        <p class="mt-4 text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Need help? Contact support at') }} <a href="mailto:support@gaeld.ch" class="text-blue-600 dark:text-blue-400 hover:underline">support@gaeld.ch</a>
        </p>
    </div>
</div>
