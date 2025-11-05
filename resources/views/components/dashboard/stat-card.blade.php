@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'trend' => null, // 'up' or 'down'
    'trendValue' => null,
    'color' => 'blue', // blue, green, orange, red
])

@php
$colorClasses = [
    'blue' => 'bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400',
    'green' => 'bg-green-50 dark:bg-green-950 text-green-600 dark:text-green-400',
    'orange' => 'bg-orange-50 dark:bg-orange-950 text-orange-600 dark:text-orange-400',
    'red' => 'bg-red-50 dark:bg-red-950 text-red-600 dark:text-red-400',
];
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800']) }}>
    <div class="p-6">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ $title }}</p>
                <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">{{ $value }}</p>
                @if($subtitle)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-500">{{ $subtitle }}</p>
                @endif
            </div>
            @if($icon)
                <div class="flex size-12 items-center justify-center rounded-lg {{ $colorClasses[$color] }}">
                    {!! $icon !!}
                </div>
            @endif
        </div>

        @if($trend && $trendValue)
            <div class="mt-4 flex items-center gap-1">
                @if($trend === 'up')
                    <svg class="size-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                @else
                    <svg class="size-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                @endif
                <span class="text-sm font-medium {{ $trend === 'up' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $trendValue }}
                </span>
            </div>
        @endif
    </div>
</div>
