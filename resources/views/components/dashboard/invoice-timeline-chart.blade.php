@props([
    'chartData',
    'year',
])

<div {{ $attributes->merge(['class' => '']) }} x-data="invoiceTimelineChart(@js($chartData), @js($year))">
    <canvas x-ref="canvas" class="max-h-[400px]"></canvas>
</div>
