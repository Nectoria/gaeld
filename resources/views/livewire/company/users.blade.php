<?php

use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
        return CompanyInvitation::where('company_id', $this->company->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('inviter')
            ->latest()
            ->get();
    }

    public function inviteUser(): void
    {
        $this->authorize('manageUsers', Company::class);

        $validated = $this->validate();

        // Check if user already belongs to company
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser && $existingUser->belongsToCompany($this->company->id)) {
            $this->addError('email', 'This user is already a member of this company.');
            return;
        }

        // Check if there's already a pending invitation
        $existingInvitation = CompanyInvitation::where('company_id', $this->company->id)
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            $this->addError('email', 'There is already a pending invitation for this email.');
            return;
        }

        // Create invitation
        $invitation = CompanyInvitation::create([
            'company_id' => $this->company->id,
            'invited_by' => Auth::id(),
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => CompanyInvitation::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        // Refresh model to ensure casts are applied and load relationships
        $invitation->refresh();
        $invitation->load(['company', 'inviter']);

        // Send invitation email
        Mail::to($validated['email'])->send(new \App\Mail\CompanyInvitationMail($invitation));

        $this->reset(['email', 'role', 'showInviteModal']);
        $this->dispatch('invitation-sent');
        session()->flash('success', 'Invitation sent successfully!');
    }

    public function removeUser(int $userId): void
    {
        $this->authorize('manageUsers', Company::class);

        // Can't remove yourself
        if ($userId === Auth::id()) {
            $this->addError('remove', 'You cannot remove yourself from the company.');
            return;
        }

        // Can't remove if it's the last owner
        $user = User::find($userId);
        $pivot = $user->companies()->where('company_id', $this->company->id)->first()?->pivot;

        if ($pivot && $pivot->isOwner()) {
            $ownersCount = $this->company->users()->wherePivot('role', 'owner')->count();
            if ($ownersCount <= 1) {
                $this->addError('remove', 'Cannot remove the last owner of the company.');
                return;
            }
        }

        $this->company->users()->detach($userId);
        session()->flash('success', 'User removed from company.');
    }

    public function cancelInvitation(int $invitationId): void
    {
        $this->authorize('manageUsers', Company::class);

        $invitation = CompanyInvitation::findOrFail($invitationId);

        if ($invitation->company_id !== $this->company->id) {
            abort(403);
        }

        $invitation->delete();
        session()->flash('success', 'Invitation cancelled.');
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

    public function updateUserRole(): void
    {
        $this->authorize('manageUsers', Company::class);

        $this->validate([
            'updateRole' => 'required|string|in:owner,admin,accountant,employee,viewer',
        ]);

        $user = User::findOrFail($this->selectedUserId);

        // Can't change your own role
        if ($user->id === Auth::id()) {
            $this->addError('updateRole', 'You cannot change your own role.');
            return;
        }

        // Update role in pivot table
        $this->company->users()->updateExistingPivot($user->id, [
            'role' => $this->updateRole,
        ]);

        // Update Spatie role
        $user->syncRoles([$this->updateRole]);

        $this->reset(['selectedUserId', 'updateRole', 'showUpdateRoleModal']);
        session()->flash('success', 'User role updated successfully.');
    }

    public function openTransferOwnershipModal(int $userId): void
    {
        $this->authorize('manageUsers', Company::class);

        // Only owners can transfer ownership
        $currentUserPivot = Auth::user()->companies()->where('company_id', $this->company->id)->first()?->pivot;
        if (!$currentUserPivot || !$currentUserPivot->isOwner()) {
            abort(403, 'Only owners can transfer ownership.');
        }

        $this->selectedUserId = $userId;
        $this->showTransferOwnershipModal = true;
    }

    public function transferOwnership(): void
    {
        $this->authorize('manageUsers', Company::class);

        $currentUser = Auth::user();
        $newOwner = User::findOrFail($this->selectedUserId);

        // Verify current user is owner
        $currentUserPivot = $currentUser->companies()->where('company_id', $this->company->id)->first()?->pivot;
        if (!$currentUserPivot || !$currentUserPivot->isOwner()) {
            abort(403, 'Only owners can transfer ownership.');
        }

        DB::transaction(function () use ($currentUser, $newOwner) {
            // Change current owner to admin
            $this->company->users()->updateExistingPivot($currentUser->id, [
                'role' => 'admin',
            ]);

            // Make selected user the new owner
            $this->company->users()->updateExistingPivot($newOwner->id, [
                'role' => 'owner',
            ]);

            // Update Spatie roles
            $currentUser->syncRoles(['admin']);
            $newOwner->syncRoles(['owner']);
        });

        $this->reset(['selectedUserId', 'showTransferOwnershipModal']);
        session()->flash('success', 'Ownership transferred successfully. You are now an admin.');
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
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <p class="text-sm text-green-800 dark:text-green-300">{{ session('success') }}</p>
                </div>
            @endif

            <div class="space-y-6">
            <!-- Active Users -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        Active Members ({{ $this->users->count() }})
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Joined</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
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
                                                        <span class="text-xs text-zinc-500">(You)</span>
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
                                        {{ $user->pivot->joined_at?->format('M d, Y') ?? 'N/A' }}
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
                                                        Change Role
                                                    </flux:button>

                                                    @if($user->pivot->role !== 'owner')
                                                        <flux:button
                                                            wire:click="openTransferOwnershipModal({{ $user->id }})"
                                                            variant="ghost"
                                                            size="sm"
                                                        >
                                                            Make Owner
                                                        </flux:button>
                                                    @endif

                                                    <flux:button
                                                        wire:click="removeUser({{ $user->id }})"
                                                        wire:confirm="Are you sure you want to remove this user?"
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        Remove
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
                            Pending Invitations ({{ $this->pendingInvitations->count() }})
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Invited By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Expires</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
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
                                                    wire:confirm="Are you sure you want to cancel this invitation?"
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    Cancel
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
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Invite User</h2>
                </div>

                <div class="space-y-4">
                    <flux:input
                        wire:model="email"
                        type="email"
                        label="Email Address"
                        placeholder="user@example.com"
                        required
                    />

                    <flux:select
                        wire:model="role"
                        label="Role"
                        required
                    >
                        <option value="admin">Admin - Full access</option>
                        <option value="accountant">Accountant - Manage invoices & contacts</option>
                        <option value="employee">Employee - Limited access</option>
                        <option value="viewer">Viewer - Read only</option>
                    </flux:select>

                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                        <p class="text-sm text-blue-800 dark:text-blue-400">
                            An invitation email will be sent with a link to join the company. The invitation will expire in 7 days.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showInviteModal', false)">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Send Invitation</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    <!-- Update Role Modal -->
    @if($showUpdateRoleModal)
        <flux:modal name="update-role-modal" wire:model="showUpdateRoleModal">
            <form wire:submit="updateUserRole" class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Update User Role</h2>
                </div>

                <div class="space-y-4">
                    <flux:select
                        wire:model="updateRole"
                        label="New Role"
                        required
                    >
                        <option value="owner">Owner - Full control including ownership transfer</option>
                        <option value="admin">Admin - Full access</option>
                        <option value="accountant">Accountant - Manage invoices & contacts</option>
                        <option value="employee">Employee - Limited access</option>
                        <option value="viewer">Viewer - Read only</option>
                    </flux:select>

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3">
                        <p class="text-sm text-yellow-800 dark:text-yellow-400">
                            Changing a user's role will update their permissions immediately.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showUpdateRoleModal', false)">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Update Role</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    <!-- Transfer Ownership Modal -->
    @if($showTransferOwnershipModal)
        <flux:modal name="transfer-ownership-modal" wire:model="showTransferOwnershipModal">
            <form wire:submit="transferOwnership" class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Transfer Ownership</h2>
                </div>

                <div class="space-y-4">
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-red-900 dark:text-red-300 mb-2">
                            Warning: This action is permanent
                        </h3>
                        <ul class="text-sm text-red-800 dark:text-red-400 space-y-1 list-disc list-inside">
                            <li>You will become an admin and lose owner privileges</li>
                            <li>The selected user will become the new owner</li>
                            <li>Only owners can transfer ownership</li>
                            <li>This action cannot be undone without the new owner's consent</li>
                        </ul>
                    </div>

                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Are you sure you want to transfer ownership of this company?
                    </p>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showTransferOwnershipModal', false)">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Transfer Ownership</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
        </div>
    </x-settings.company-layout>
</section>
