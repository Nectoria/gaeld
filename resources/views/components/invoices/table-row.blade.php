@props(['invoice'])

@php
use App\Services\InvoiceCalculationService;

$statusBadgeColor = match($invoice->status) {
    'paid' => 'green',
    'sent' => 'blue',
    'draft' => 'zinc',
    'overdue' => 'red',
    'cancelled' => 'zinc',
    default => 'zinc',
};

$calculator = app(InvoiceCalculationService::class);
@endphp

<tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
    <td class="px-6 py-4">
        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
            {{ $invoice->invoice_number }}
        </div>
        @if($invoice->reference_number)
            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Ref:') }} {{ $invoice->reference_number }}
            </div>
        @endif
    </td>
    <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
        {{ $invoice->invoice_date->format('M d, Y') }}
    </td>
    <td class="px-6 py-4">
        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
            {{ $invoice->contact->name }}
        </div>
    </td>
    <td class="px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100 font-medium">
        {{ $invoice->currency }} {{ $calculator->formatMoney($invoice->total_amount, $invoice->currency) }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <flux:badge :color="$statusBadgeColor" size="sm">
            {{ ucfirst($invoice->status) }}
        </flux:badge>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
        <div class="flex items-center gap-2">
            <a
                href="{{ route('invoices.show', $invoice) }}"
                class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300"
                wire:navigate
            >
                {{ __('View') }}
            </a>
            @if($invoice->status === 'draft')
                <a
                    href="{{ route('invoices.edit', $invoice) }}"
                    class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300"
                    wire:navigate
                >
                    {{ __('Edit') }}
                </a>
            @endif
        </div>
    </td>
</tr>
