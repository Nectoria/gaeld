<?php

use App\Models\Company;
use App\Services\TenantService;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    #[Computed]
    public function currentCompany(): ?Company
    {
        return currentCompany();
    }

    #[Computed]
    public function companies()
    {
        return tenant()->accessible();
    }

    public function switchCompany(int $companyId): void
    {
        if (tenant()->switch($companyId)) {
            $this->dispatch('company-switched');
            $this->redirect(route('dashboard'), navigate: true);
        }
    }
}; ?>

<div>
    @if($this->companies->count() > 1)
        <div class="px-2 mb-4">
            <flux:dropdown class="w-full">
                <flux:button variant="ghost" class="w-full justify-between" size="sm">
                    <div class="flex items-center gap-2 truncate">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span class="truncate text-sm">{{ $this->currentCompany?->name ?? __('No Company') }}</span>
                    </div>
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                    </svg>
                </flux:button>

                <flux:menu class="w-full">
                    <div class="px-3 py-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                        {{ __('Switch Company') }}
                    </div>
                    @foreach($this->companies as $company)
                        <flux:menu.item
                            wire:click="switchCompany({{ $company->id }})"
                            :class="$this->currentCompany?->id === $company->id ? 'bg-zinc-100 dark:bg-zinc-800' : ''"
                        >
                            <div class="flex items-center justify-between w-full">
                                <span class="truncate">{{ $company->name }}</span>
                                @if($this->currentCompany?->id === $company->id)
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        </div>
    @elseif($this->currentCompany)
        <div class="px-3 py-2 mb-4">
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span class="truncate font-medium">{{ $this->currentCompany->name }}</span>
            </div>
        </div>
    @endif
</div>
