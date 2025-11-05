<?php

use App\Models\Invoice;
use App\Services\QrInvoiceGenerator;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);
        $this->invoice = $invoice->load(['contact', 'items', 'creator', 'company']);
    }

    public function formatMoney(int $cents, string $currency = 'CHF'): string
    {
        return number_format($cents / 100, 2, '.', '\'') . ' ' . $currency;
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'draft' => 'zinc',
            'sent' => 'blue',
            'viewed' => 'indigo',
            'partial' => 'yellow',
            'paid' => 'green',
            'overdue' => 'red',
            'cancelled' => 'zinc',
            default => 'zinc',
        };
    }

    public function markAsPaid(): void
    {
        $this->authorize('markAsPaid', $this->invoice);

        $this->invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_amount' => $this->invoice->total_amount,
        ]);

        $this->dispatch('invoice-updated');
    }

    public function sendInvoice(): void
    {
        $this->authorize('send', $this->invoice);

        $this->invoice->update(['status' => 'sent']);

        // TODO: Send email to customer

        $this->dispatch('invoice-sent');
    }

    public function cancelInvoice(): void
    {
        $this->authorize('delete', $this->invoice);

        $this->invoice->update(['status' => 'cancelled']);

        $this->dispatch('invoice-cancelled');
    }

    public function downloadPdf(): StreamedResponse
    {
        $this->authorize('generateQr', $this->invoice);

        $generator = app(QrInvoiceGenerator::class);
        $path = $generator->generate($this->invoice, app()->getLocale());

        return response()->streamDownload(function () use ($path) {
            readfile($path);
        }, 'invoice_' . $this->invoice->invoice_number . '.pdf');
    }
}; ?>

<x-page-layout max-width="5xl">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <x-back-link :href="route('invoices.index')" :label="__('Back to Invoices')" />

                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">
                    {{ $invoice->invoice_number }}
                </h1>
                <div class="flex items-center gap-3 mt-2">
                    <flux:badge :color="$this->getStatusColor($invoice->status)" size="lg">
                        {{ ucfirst($invoice->status) }}
                    </flux:badge>
                    @if($invoice->isOverdue())
                        <span class="text-sm text-red-600 dark:text-red-400 font-medium">
                            {{ __('Overdue by :time', ['time' => $invoice->due_date->diffForHumans(now(), true)]) }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                @can('update', $invoice)
                    @if($invoice->status === 'draft')
                        <flux:button href="{{ route('invoices.edit', $invoice) }}" icon="pencil" wire:navigate>
                            {{ __('Edit') }}
                        </flux:button>
                    @endif
                @endcan

                @can('send', $invoice)
                    @if($invoice->status === 'draft')
                        <flux:button wire:click="sendInvoice" variant="primary">
                            {{ __('Send Invoice') }}
                        </flux:button>
                    @endif
                @endcan

                @can('markAsPaid', $invoice)
                    @if(!$invoice->isPaid())
                        <flux:button wire:click="markAsPaid" variant="filled">
                            {{ __('Mark as Paid') }}
                        </flux:button>
                    @endif
                @endcan

                @can('generateQr', $invoice)
                    <flux:button wire:click="downloadPdf" variant="ghost" icon="arrow-down-tray">
                        {{ __('Download PDF') }}
                    </flux:button>
                @endcan
            </div>
        </div>
    </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Invoice Details -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <!-- From -->
                        <div>
                            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-2">{{ __('From') }}</h3>
                            <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                <p class="font-semibold">{{ $invoice->company->name }}</p>
                                @if($invoice->company->legal_name && $invoice->company->legal_name !== $invoice->company->name)
                                    <p>{{ $invoice->company->legal_name }}</p>
                                @endif
                                <p>{{ $invoice->company->street }} {{ $invoice->company->street_number }}</p>
                                <p>{{ $invoice->company->postal_code }} {{ $invoice->company->city }}</p>
                                @if($invoice->company->vat_number)
                                    <p class="mt-2">VAT: {{ $invoice->company->vat_number }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- To -->
                        <div>
                            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-2">{{ __('Bill To') }}</h3>
                            <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                <p class="font-semibold">{{ $invoice->contact->name }}</p>
                                @if($invoice->contact->contact_person)
                                    <p>{{ $invoice->contact->contact_person }}</p>
                                @endif
                                <p>{{ $invoice->contact->street }} {{ $invoice->contact->street_number }}</p>
                                <p>{{ $invoice->contact->postal_code }} {{ $invoice->contact->city }}</p>
                                @if($invoice->contact->email)
                                    <p class="mt-2">{{ $invoice->contact->email }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-6 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <div>
                            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Invoice Date') }}</h3>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $invoice->invoice_date->format('d.m.Y') }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Due Date') }}</h3>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $invoice->due_date->format('d.m.Y') }}
                            </p>
                        </div>
                        @if($invoice->paid_at)
                            <div>
                                <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Paid Date') }}</h3>
                                <p class="text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $invoice->paid_at->format('d.m.Y') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Items') }}</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Item') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Qty') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Unit Price') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($invoice->items as $item)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $item->name }}
                                            </div>
                                            @if($item->description)
                                                <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                                    {{ $item->description }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $item->quantity }} {{ $item->unit }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $this->formatMoney($item->unit_price, $invoice->currency) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $this->formatMoney($item->subtotal, $invoice->currency) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Additional Info -->
                @if($invoice->terms || $invoice->notes)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        @if($invoice->terms)
                            <div class="mb-4">
                                <h3 class="text-sm font-medium text-zinc-900 dark:text-white mb-2">{{ __('Terms & Conditions') }}</h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-line">{{ $invoice->terms }}</p>
                            </div>
                        @endif
                        @if($invoice->notes)
                            <div>
                                <h3 class="text-sm font-medium text-zinc-900 dark:text-white mb-2">{{ __('Internal Notes') }}</h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-line">{{ $invoice->notes }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Totals -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Summary') }}</h2>

                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ __('Subtotal') }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">
                                {{ $this->formatMoney($invoice->subtotal_amount, $invoice->currency) }}
                            </span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">
                                {{ __('Tax (:rate%)', ['rate' => number_format($invoice->tax_rate, 2)]) }}
                            </span>
                            <span class="font-medium text-zinc-900 dark:text-white">
                                {{ $this->formatMoney($invoice->tax_amount, $invoice->currency) }}
                            </span>
                        </div>

                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 mt-2">
                            <div class="flex justify-between">
                                <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Total') }}</span>
                                <span class="text-lg font-bold text-zinc-900 dark:text-white">
                                    {{ $this->formatMoney($invoice->total_amount, $invoice->currency) }}
                                </span>
                            </div>
                        </div>

                        @if($invoice->paid_amount > 0 && !$invoice->isPaid())
                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 mt-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('Paid') }}</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">
                                        {{ $this->formatMoney($invoice->paid_amount, $invoice->currency) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-semibold text-zinc-900 dark:text-white">{{ __('Balance Due') }}</span>
                                    <span class="font-bold text-zinc-900 dark:text-white">
                                        {{ $this->formatMoney($invoice->total_amount - $invoice->paid_amount, $invoice->currency) }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                @if($invoice->status !== 'cancelled')
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Quick Actions') }}</h3>
                        <div class="space-y-2">
                            @can('delete', $invoice)
                                <flux:button wire:click="cancelInvoice" variant="ghost" class="w-full justify-start" :wire:confirm="__('Are you sure you want to cancel this invoice?')">
                                    {{ __('Cancel Invoice') }}
                                </flux:button>
                            @endcan
                        </div>
                    </div>
                @endif
            </div>
        </div>
</x-page-layout>
