@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
$typeStyles = [
    'info' => [
        'container' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
        'title' => 'text-blue-900 dark:text-blue-300',
        'text' => 'text-blue-800 dark:text-blue-400',
        'icon' => 'text-blue-600 dark:text-blue-400',
    ],
    'success' => [
        'container' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        'title' => 'text-green-900 dark:text-green-300',
        'text' => 'text-green-800 dark:text-green-300',
        'icon' => 'text-green-600 dark:text-green-400',
    ],
    'warning' => [
        'container' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
        'title' => 'text-yellow-900 dark:text-yellow-300',
        'text' => 'text-yellow-800 dark:text-yellow-400',
        'icon' => 'text-yellow-600 dark:text-yellow-400',
    ],
    'error' => [
        'container' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        'title' => 'text-red-900 dark:text-red-300',
        'text' => 'text-red-800 dark:text-red-400',
        'icon' => 'text-red-600 dark:text-red-400',
    ],
];

$styles = $typeStyles[$type] ?? $typeStyles['info'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg p-4 ' . $styles['container']]) }}>
    @if($title)
        <div class="flex items-start">
            <div class="flex-1">
                <h3 class="text-sm font-medium {{ $styles['title'] }} mb-2">
                    {{ $title }}
                </h3>
                <div class="text-sm {{ $styles['text'] }}">
                    {{ $slot }}
                </div>
            </div>
            @if($dismissible)
                <button type="button" class="ml-3 -mr-1 -mt-1" @click="$el.closest('[x-data]').remove()">
                    <svg class="h-5 w-5 {{ $styles['icon'] }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            @endif
        </div>
    @else
        <div class="text-sm {{ $styles['text'] }}">
            {{ $slot }}
        </div>
    @endif
</div>
