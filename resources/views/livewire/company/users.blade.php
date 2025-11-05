<?php

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyInvitationService;
use App\Services\CompanyUserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    public Company $company;

    public bool $showInviteModal = false;
    public bool $showUpdateRoleModal = false;
    public bool $showTransferOwnershipModal = false;
    public ?int $selectedUserId = null;

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|in:admin,accountant,employee,viewer')]
    public string $role = 'employee';

    public string $updateRole = '';

    public function mount(): void
    {
        $this->authorize('manageUsers', Company::class);
        $this->company = currentCompany()->load(['users']);
    }

    #[Computed]
    public function users()
    {
        return $this->company->users()->withPivot('role', 'is_active', 'joined_at')->get();
    }

    #[Computed]
    public function pendingInvitations()
    {
        return app(CompanyInvitationService::class)->getPendingInvitations($this->company);
    }

    public function inviteUser(CompanyInvitationService $invitationService): void
    {
        $this->authorize('manageUsers', Company::class);

        $validated = $this->validate();

        try {
            $invitationService->createInvitation(
                $this->company,
                $validated['email'],
                $validated['role'],
                Auth::id()
            );

            $this->reset(['email', 'role', 'showInviteModal']);
            $this->dispatch('invitation-sent');
            session()->flash('success', __('Invitation sent successfully!'));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }
        }
    }

    public function removeUser(int $userId, CompanyUserService $userService): void
    {
        $this->authorize('manageUsers', Company::class);

        try {
            $userService->removeUser($this->company, $userId, Auth::id());
            session()->flash('success', __('User removed from company.'));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }
        }
    }

    public function cancelInvitation(int $invitationId, CompanyInvitationService $invitationService): void
    {
        $this->authorize('manageUsers', Company::class);

        try {
            $invitationService->cancelInvitation($invitationId, $this->company->id);
            session()->flash('success', __('Invitation cancelled.'));
        } catch (\Exception $e) {
            abort(403);
        }
    }

    public function openUpdateRoleModal(int $userId): void
    {
        $this->authorize('manageUsers', Company::class);

        $user = User::findOrFail($userId);
        $pivot = $user->companies()->where('company_id', $this->company->id)->first()?->pivot;

        if (!$pivot) {
            abort(404);
        }

        $this->selectedUserId = $userId;
        $this->updateRole = $pivot->role;
        $this->showUpdateRoleModal = true;
    }

    public function updateUserRole(CompanyUserService $userService): void
    {
        $this->authorize('manageUsers', Company::class);

        $this->validate([
            'updateRole' => 'required|string|in:owner,admin,accountant,employee,viewer',
        ]);

        try {
            $userService->updateUserRole($this->company, $this->selectedUserId, $this->updateRole, Auth::id());
            $this->reset(['selectedUserId', 'updateRole', 'showUpdateRoleModal']);
            session()->flash('success', __('User role updated successfully.'));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }
        }
    }

    public function openTransferOwnershipModal(int $userId): void
    {
        $this->authorize('manageUsers', Company::class);

        // Only owners can transfer ownership
        $currentUserPivot = Auth::user()->companies()->where('company_id', $this->company->id)->first()?->pivot;
        if (!$currentUserPivot || !$currentUserPivot->isOwner()) {
            abort(403, __('Only owners can transfer ownership.'));
        }

        $this->selectedUserId = $userId;
        $this->showTransferOwnershipModal = true;
    }

    public function transferOwnership(CompanyUserService $userService): void
    {
        $this->authorize('manageUsers', Company::class);

        try {
            $userService->transferOwnership($this->company, Auth::user(), $this->selectedUserId);
            $this->reset(['selectedUserId', 'showTransferOwnershipModal']);
            session()->flash('success', __('Ownership transferred successfully. You are now an admin.'));
        } catch (\Exception $e) {
            abort(403, $e->getMessage());
        }
    }

    public function getRoleBadgeColor(string $role): string
    {
        return match ($role) {
            'owner' => 'purple',
            'admin' => 'blue',
            'accountant' => 'green',
            'employee' => 'zinc',
            'viewer' => 'zinc',
            default => 'zinc',
        };
    }
}; ?>

@section('title', __('Company Users'))
<section class="w-full">
    @include('partials.company-settings-heading')

    <x-settings.company-layout :heading="__('Team Members')" :subheading="__('Manage users and invitations for your company')">
        <div class="my-6 w-full">
            <!-- Invite Button -->
            @can('manage_company_users')
                <div class="mb-6 flex justify-end">
                    <flux:button wire:click="$set('showInviteModal', true)" icon="plus">
                        {{ __('Invite User') }}
                    </flux:button>
                </div>
            @endcan

            @if (session('success'))
                <x-alert type="success" class="mb-6">
                    {{ session('success') }}
                </x-alert>
            @endif

            <div class="space-y-6">
            <!-- Active Users -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ __('Active Members (:count)', ['count' => $this->users->count()]) }}
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('User') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Role') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Joined') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($this->users as $user)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                                                    {{ $user->initials() }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $user->name }}
                                                    @if($user->id === Auth::id())
                                                        <span class="text-xs text-zinc-500">{{ __('(You)') }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                                    {{ $user->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <flux:badge :color="$this->getRoleBadgeColor($user->pivot->role)" size="sm">
                                            {{ ucfirst($user->pivot->role) }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $user->pivot->joined_at?->format('M d, Y') ?? __('N/A') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @if($user->id !== Auth::id())
                                                @can('manage_company_users')
                                                    <flux:button
                                                        wire:click="openUpdateRoleModal({{ $user->id }})"
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        {{ __('Change Role') }}
                                                    </flux:button>

                                                    @if($user->pivot->role !== 'owner')
                                                        <flux:button
                                                            wire:click="openTransferOwnershipModal({{ $user->id }})"
                                                            variant="ghost"
                                                            size="sm"
                                                        >
                                                            {{ __('Make Owner') }}
                                                        </flux:button>
                                                    @endif

                                                    <flux:button
                                                        wire:click="removeUser({{ $user->id }})"
                                                        wire:confirm="{{ __('Are you sure you want to remove this user?') }}"
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        {{ __('Remove') }}
                                                    </flux:button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Invitations -->
            @if($this->pendingInvitations->count() > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ __('Pending Invitations (:count)', ['count' => $this->pendingInvitations->count()]) }}
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Email') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Role') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Invited By') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Expires') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($this->pendingInvitations as $invitation)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $invitation->email }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <flux:badge :color="$this->getRoleBadgeColor($invitation->role)" size="sm">
                                                {{ ucfirst($invitation->role) }}
                                            </flux:badge>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $invitation->inviter->name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $invitation->expires_at->diffForHumans() }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @can('manage_company_users')
                                                <flux:button
                                                    wire:click="cancelInvitation({{ $invitation->id }})"
                                                    wire:confirm="{{ __('Are you sure you want to cancel this invitation?') }}"
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    {{ __('Cancel') }}
                                                </flux:button>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            </div>

            <!-- Invite Modal -->
    @if($showInviteModal)
        <flux:modal name="invite-user-modal" wire:model="showInviteModal">
            <form wire:submit="inviteUser" class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Invite User') }}</h2>
                </div>

                <div class="space-y-4">
                    <flux:input
                        wire:model="email"
                        type="email"
                        :label="__('Email Address')"
                        placeholder="user@example.com"
                        required
                    />

                    <flux:select
                        wire:model="role"
                        :label="__('Role')"
                        required
                    >
                        <option value="admin">{{ __('Admin - Full access') }}</option>
                        <option value="accountant">{{ __('Accountant - Manage invoices & contacts') }}</option>
                        <option value="employee">{{ __('Employee - Limited access') }}</option>
                        <option value="viewer">{{ __('Viewer - Read only') }}</option>
                    </flux:select>

                    <x-alert type="info">
                        {{ __('An invitation email will be sent with a link to join the company. The invitation will expire in 7 days.') }}
                    </x-alert>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showInviteModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Send Invitation') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    <!-- Update Role Modal -->
    @if($showUpdateRoleModal)
        <flux:modal name="update-role-modal" wire:model="showUpdateRoleModal">
            <form wire:submit="updateUserRole" class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Update User Role') }}</h2>
                </div>

                <div class="space-y-4">
                    <flux:select
                        wire:model="updateRole"
                        :label="__('New Role')"
                        required
                    >
                        <option value="owner">{{ __('Owner - Full control including ownership transfer') }}</option>
                        <option value="admin">{{ __('Admin - Full access') }}</option>
                        <option value="accountant">{{ __('Accountant - Manage invoices & contacts') }}</option>
                        <option value="employee">{{ __('Employee - Limited access') }}</option>
                        <option value="viewer">{{ __('Viewer - Read only') }}</option>
                    </flux:select>

                    <x-alert type="warning">
                        {{ __('Changing a user\'s role will update their permissions immediately.') }}
                    </x-alert>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showUpdateRoleModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Update Role') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    <!-- Transfer Ownership Modal -->
    @if($showTransferOwnershipModal)
        <flux:modal name="transfer-ownership-modal" wire:model="showTransferOwnershipModal">
            <form wire:submit="transferOwnership" class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Transfer Ownership') }}</h2>
                </div>

                <div class="space-y-4">
                    <x-alert type="error" :title="__('Warning: This action is permanent')">
                        <ul class="space-y-1 list-disc list-inside">
                            <li>{{ __('You will become an admin and lose owner privileges') }}</li>
                            <li>{{ __('The selected user will become the new owner') }}</li>
                            <li>{{ __('Only owners can transfer ownership') }}</li>
                            <li>{{ __('This action cannot be undone without the new owner\'s consent') }}</li>
                        </ul>
                    </x-alert>

                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Are you sure you want to transfer ownership of this company?') }}
                    </p>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showTransferOwnershipModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Transfer Ownership') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
        </div>
    </x-settings.company-layout>
</section>
