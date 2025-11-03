<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Onboarding routes (no company required)
Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('onboarding/create-company', 'onboarding.create-company')
        ->name('onboarding.create-company');
});

// Invitation routes (public access for acceptance)
Volt::route('invitations/{token}/accept', 'invitations.accept')
    ->name('invitations.accept');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'has.company'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'has.company'])->group(function () {
    // Contact routes
    Volt::route('contacts', 'contacts.index')
        ->name('contacts.index');
    Volt::route('contacts/create', 'contacts.create')
        ->name('contacts.create');
    Volt::route('contacts/{contact}/edit', 'contacts.edit')
        ->name('contacts.edit');

    Volt::route('banking', 'banking.index')->name('banking');

    // Invoice routes
    Volt::route('invoices', 'invoices.index')
        ->name('invoices.index');
    Volt::route('invoices/create', 'invoices.create')
        ->name('invoices.create');
    Volt::route('invoices/{invoice}', 'invoices.show')
        ->name('invoices.show');
    Volt::route('invoices/{invoice}/edit', 'invoices.edit')
        ->name('invoices.edit');

    // Company routes
    Volt::route('company/settings', 'company.settings')
        ->name('company.settings');
    Volt::route('company/users', 'company.users')
        ->name('company.users');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
