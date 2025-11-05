@props([
    'title',
    'description' => null,
    'backHref' => null,
    'backLabel' => 'Back',
])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="flex items-center justify-between">
        <div>
            @if($backHref)
                <x-back-link :href="$backHref" :label="$backLabel" />
            @endif

            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white @if($backHref) mt-2 @endif">
                {{ $title }}
            </h1>

            @if($description)
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $description }}
                </p>
            @endif
        </div>

        @if(isset($action))
            <div class="flex items-center gap-2">
                {{ $action }}
            </div>
        @endif
    </div>
</div>
