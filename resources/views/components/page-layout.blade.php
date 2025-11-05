@props([
    'maxWidth' => '7xl', // Options: 'full', '7xl', '5xl', '4xl', '3xl', '2xl'
])

@php
$maxWidthClass = match($maxWidth) {
    'full' => '',
    '7xl' => 'max-w-7xl',
    '5xl' => 'max-w-5xl',
    '4xl' => 'max-w-4xl',
    '3xl' => 'max-w-3xl',
    '2xl' => 'max-w-2xl',
    default => 'max-w-7xl',
};
@endphp

<div {{ $attributes->merge(['class' => trim($maxWidthClass . ' mx-auto py-6 px-4 sm:px-6 lg:px-8')]) }}>
    {{ $slot }}
</div>
