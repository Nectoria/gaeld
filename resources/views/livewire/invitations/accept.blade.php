<?php

use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component {
    public ?CompanyInvitation $invitation = null;
    public ?string $error = null;
    public bool $accepted = false;

    public function mount(string $token): void
    {
        $this->invitation = CompanyInvitation::where('token', $token)
            ->with(['company', 'inviter'])
            ->first();

        if (!$this->invitation) {
            $this->error = __('Invitation not found.');
            return;
        }

        if ($this->invitation->isExpired()) {
            $this->error = __('This invitation has expired.');
            return;
        }

        if ($this->invitation->isAccepted()) {
            $this->error = __('This invitation has already been accepted.');
            return;
        }

        // Check if logged in user's email matches invitation
        if (Auth::check() && Auth::user()->email !== $this->invitation->email) {
            $this->error = __('This invitation was sent to a different email address.');
            return;
        }
    }

    public function acceptInvitation(): void
    {
        if (!Auth::check()) {
            session()->put('invitation_token', $this->invitation->token);
            $this->redirect(route('login'), navigate: true);
            return;
        }

        if (!$this->invitation || $this->error) {
            return;
        }

        $user = Auth::user();

        // Check if user already belongs to company
        if ($user->belongsToCompany($this->invitation->company_id)) {
            $this->error = __('You are already a member of this company.');
            return;
        }

        DB::transaction(function () use ($user) {
            // Add user to company
            $this->invitation->company->users()->attach($user->id, [
                'role' => $this->invitation->role,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            // Assign role if user doesn't have it
            $roleName = $this->invitation->role;
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            // Mark invitation as accepted
            $this->invitation->update([
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ]);

            // Switch to new company
            tenant()->switch($this->invitation->company_id);

            // Clear cache
            tenant()->clearCache($user);
        });

        $this->accepted = true;
    }

    public function declineInvitation(): void
    {
        if ($this->invitation) {
            $this->invitation->delete();
        }

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <x-app-logo class="mx-auto h-12 w-auto" />
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-lg p-8">
            @if($error)
                <!-- Error State -->
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h2 class="mt-4 text-xl font-bold text-zinc-900 dark:text-white">
                        {{ __('Invalid Invitation') }}
                    </h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $error }}
                    </p>
                    <div class="mt-6">
                        <flux:button href="{{ route('dashboard') }}" variant="primary" wire:navigate>
                            {{ __('Go to Dashboard') }}
                        </flux:button>
                    </div>
                </div>
            @elseif($accepted)
                <!-- Success State -->
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="mt-4 text-xl font-bold text-zinc-900 dark:text-white">
                        {{ __('Welcome to :company!', ['company' => $invitation->company->name]) }}
                    </h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __("You've successfully joined the company as :role.", ['role' => ucfirst($invitation->role)]) }}
                    </p>
                    <div class="mt-6">
                        <flux:button href="{{ route('dashboard') }}" variant="primary" wire:navigate>
                            {{ __('Go to Dashboard') }}
                        </flux:button>
                    </div>
                </div>
            @else
                <!-- Invitation Details -->
                <div>
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ __("You've Been Invited!") }}
                        </h2>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __(':inviter has invited you to join their company', ['inviter' => $invitation->inviter->name]) }}
                        </p>
                    </div>

                    <!-- Company Info -->
                    <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4 mb-6">
                        <dl class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Company:') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $invitation->company->name }}</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Your Role:') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ ucfirst($invitation->role) }}</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Invited By:') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $invitation->inviter->name }}</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Expires:') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $invitation->expires_at->diffForHumans() }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Role Description -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
                            {{ __("As :role, you'll be able to:", ['role' => ucfirst($invitation->role)]) }}
                        </h3>
                        <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1 list-disc list-inside">
                            @if($invitation->role === 'admin')
                                <li>{{ __('Full access to all features') }}</li>
                                <li>{{ __('Manage company settings') }}</li>
                                <li>{{ __('Manage users and permissions') }}</li>
                            @elseif($invitation->role === 'accountant')
                                <li>{{ __('Manage invoices and contacts') }}</li>
                                <li>{{ __('View financial reports') }}</li>
                                <li>{{ __('Export data') }}</li>
                            @elseif($invitation->role === 'employee')
                                <li>{{ __('View and create your own invoices') }}</li>
                                <li>{{ __('View contacts') }}</li>
                                <li>{{ __('Limited access to features') }}</li>
                            @else
                                <li>{{ __('View invoices and contacts') }}</li>
                                <li>{{ __('Read-only access') }}</li>
                            @endif
                        </ul>
                    </div>

                    @if(!Auth::check())
                        <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                            <p class="text-sm text-yellow-800 dark:text-yellow-400">
                                {{ __('You need to log in or create an account to accept this invitation.') }}
                            </p>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <flux:button
                            wire:click="acceptInvitation"
                            variant="primary"
                            class="flex-1"
                        >
                            {{ __('Accept Invitation') }}
                        </flux:button>
                        <flux:button
                            wire:click="declineInvitation"
                            wire:confirm="{{ __('Are you sure you want to decline this invitation?') }}"
                            variant="ghost"
                        >
                            {{ __('Decline') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
