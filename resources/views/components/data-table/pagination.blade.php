@props(['paginator'])

@if($paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'px-4 py-3 border-t border-zinc-200 dark:border-zinc-700']) }}>
        {{ $paginator->links() }}
    </div>
@endif
