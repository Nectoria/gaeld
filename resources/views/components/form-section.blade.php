@props([
    'title',
    'description' => null,
    'columns' => 1,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-zinc-800 rounded-lg shadow p-6']) }}>
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
            {{ $title }}
        </h2>
        @if($description)
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $description }}
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 @if($columns > 1) md:grid-cols-{{ $columns }} @endif gap-4">
        {{ $slot }}
    </div>
</div>
