@props(['totals', 'currency' => 'CHF', 'taxRate'])

<div class="flex justify-end mb-6">
    <div class="w-1/3">
        <table class="w-full" style="border-collapse: collapse;">
            <!-- Subtotal -->
            <tr>
                <td class="py-2 text-right pr-4">Subtotal:</td>
                <td class="py-2 text-right font-semibold">{{ $currency }} {{ $totals['subtotal_formatted'] }}</td>
            </tr>

            <!-- Tax -->
            <tr>
                <td class="py-2 text-right pr-4">VAT ({{ number_format($taxRate, 2) }}%):</td>
                <td class="py-2 text-right font-semibold">{{ $currency }} {{ $totals['tax_amount_formatted'] }}</td>
            </tr>

            <!-- Total -->
            <tr style="border-top: 2px solid #000;">
                <td class="py-3 text-right pr-4 text-lg"><strong>Total:</strong></td>
                <td class="py-3 text-right text-lg font-bold">{{ $currency }} {{ $totals['total_formatted'] }}</td>
            </tr>
        </table>
    </div>
</div>
