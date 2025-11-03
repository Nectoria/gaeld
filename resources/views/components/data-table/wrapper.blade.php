@props(['emptyMessage' => 'No records found', 'emptyIcon' => true])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden']) }}>
    {{ $slot }}
</div>
