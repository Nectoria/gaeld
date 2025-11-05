@props(['showClear' => false])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-zinc-800 rounded-lg shadow mb-6']) }}>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        {{ $slot }}

        @if ($showClear)
            <div class="flex items-end">
                <flux:button wire:click="clearFilters" variant="ghost">
                    {{ __('Clear Filters') }}
                </flux:button>
            </div>
        @endif
    </div>
</div>
