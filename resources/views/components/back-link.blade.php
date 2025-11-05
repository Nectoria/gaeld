@props([
    'href',
    'label' => 'Back',
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 mb-2 inline-flex items-center']) }}
    wire:navigate
>
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
    </svg>
    {{ $label }}
</a>
