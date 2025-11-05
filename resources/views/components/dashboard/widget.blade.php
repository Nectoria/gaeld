@props([
    'title' => null,
    'icon' => null,
    'loading' => false,
])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800']) }}>
    @if($loading)
        <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
    @else
        @if($title || $icon)
            <div class="flex items-center justify-between border-b border-neutral-200 dark:border-neutral-700 px-6 py-4">
                <div class="flex items-center gap-3">
                    @if($icon)
                        <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                            {!! $icon !!}
                        </div>
                    @endif
                    @if($title)
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
                    @endif
                </div>
                @if(isset($actions))
                    <div>
                        {{ $actions }}
                    </div>
                @endif
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>
    @endif
</div>
