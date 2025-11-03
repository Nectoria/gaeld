@props(['items', 'currency' => 'CHF'])

@php
use App\Services\InvoiceCalculationService;
$calculator = app(InvoiceCalculationService::class);
@endphp

<table class="w-full mb-6" style="border-collapse: collapse;">
    <thead>
        <tr style="border-bottom: 2px solid #000;">
            <th class="text-left py-2 px-2" style="width: 40%;">Description</th>
            <th class="text-right py-2 px-2" style="width: 10%;">Quantity</th>
            <th class="text-left py-2 px-2" style="width: 10%;">Unit</th>
            <th class="text-right py-2 px-2" style="width: 15%;">Unit Price</th>
            @if($items->where('discount_percent', '>', 0)->count() > 0)
                <th class="text-right py-2 px-2" style="width: 10%;">Discount</th>
            @endif
            <th class="text-right py-2 px-2" style="width: 15%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td class="py-3 px-2">
                    <div class="font-semibold">{{ $item->name }}</div>
                    @if($item->description)
                        <div class="text-sm text-gray-600 mt-1">{{ $item->description }}</div>
                    @endif
                </td>
                <td class="text-right py-3 px-2">{{ number_format($item->quantity, 2) }}</td>
                <td class="py-3 px-2">{{ $item->unit }}</td>
                <td class="text-right py-3 px-2">{{ $currency }} {{ $calculator->formatMoney($item->unit_price, $currency) }}</td>
                @if($items->where('discount_percent', '>', 0)->count() > 0)
                    <td class="text-right py-3 px-2">
                        @if($item->discount_percent > 0)
                            {{ number_format($item->discount_percent, 2) }}%
                        @else
                            -
                        @endif
                    </td>
                @endif
                <td class="text-right py-3 px-2 font-semibold">
                    {{ $currency }} {{ $calculator->formatMoney($item->subtotal, $currency) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
