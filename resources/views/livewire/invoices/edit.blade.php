<?php

use App\Models\Contact;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public Invoice $invoice;

    // Invoice fields
    public ?int $contact_id = null;
    public string $invoice_date = '';
    public int $payment_term_days = 30;
    public string $due_date = '';
    public float $tax_rate = 8.1;
    public bool $tax_inclusive = false;
    public string $notes = '';
    public string $terms = '';
    public string $footer = '';

    // Invoice items
    public array $items = [];

    public function mount(Invoice $invoice): void
    {
        $this->authorize('update', $invoice);

        $this->invoice = $invoice->load(['items']);

        // Load invoice data
        $this->contact_id = $invoice->contact_id;
        $this->invoice_date = $invoice->invoice_date->format('Y-m-d');
        $this->due_date = $invoice->due_date->format('Y-m-d');
        $this->tax_rate = (float) $invoice->tax_rate;
        $this->tax_inclusive = $invoice->tax_inclusive;
        $this->notes = $invoice->notes ?? '';
        $this->terms = $invoice->terms ?? '';
        $this->footer = $invoice->footer ?? '';

        // Load items
        $this->items = $invoice->items->map(fn($item) => [
            'name' => $item->name,
            'description' => $item->description ?? '',
            'quantity' => (float) $item->quantity,
            'unit' => $item->unit,
            'unit_price' => $item->unit_price / 100,
            'discount_percent' => (float) $item->discount_percent,
        ])->toArray();
    }

    #[Computed]
    public function contacts()
    {
        return Contact::customers()->active()->orderBy('name')->get();
    }

    public function calculateDueDate(): void
    {
        if ($this->invoice_date) {
            $this->due_date = now()
                ->parse($this->invoice_date)
                ->addDays($this->payment_term_days)
                ->format('Y-m-d');
        }
    }

    public function updatedPaymentTermDays(): void
    {
        $this->calculateDueDate();
    }

    public function updatedInvoiceDate(): void
    {
        $this->calculateDueDate();
    }

    public function updatedContactId(): void
    {
        if ($this->contact_id) {
            $contact = Contact::find($this->contact_id);
            if ($contact) {
                $this->payment_term_days = $contact->payment_term_days;
                $this->calculateDueDate();
            }
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'name' => '',
            'description' => '',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 0,
            'discount_percent' => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    #[Computed]
    public function invoiceTotals(): array
    {
        return app(InvoiceService::class)->calculateTotals($this->items, $this->tax_rate);
    }

    public function formatMoney(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '\'');
    }

    public function getItemLineTotal(array $item): int
    {
        $quantity = (float) ($item['quantity'] ?? 1);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        return (int) ($quantity * $unitPrice * 100);
    }

    public function update(): void
    {
        $this->authorize('update', $this->invoice);

        $validated = $this->validate([
            'contact_id' => 'required|exists:contacts,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $invoice = app(InvoiceService::class)->updateInvoice(
            $this->invoice,
            [
                'contact_id' => $this->contact_id,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'tax_rate' => $this->tax_rate,
                'tax_inclusive' => $this->tax_inclusive,
                'notes' => $this->notes,
                'terms' => $this->terms,
                'footer' => $this->footer,
                'items' => $this->items,
            ]
        );

        $this->redirect(route('invoices.show', $invoice), navigate: true);
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('invoices.show', $invoice) }}" class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 mb-2 inline-flex items-center" wire:navigate>
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Invoice
                    </a>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">Edit Invoice</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $invoice->invoice_number }}
                    </p>
                </div>
                <flux:button href="{{ route('invoices.show', $invoice) }}" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
            </div>
        </div>

        <form wire:submit="update">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Invoice Details
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Customer -->
                            <div class="md:col-span-2">
                                <flux:select
                                    wire:model.live="contact_id"
                                    label="Customer"
                                    placeholder="Select a customer"
                                    required
                                >
                                    @foreach($this->contacts as $contact)
                                        <option value="{{ $contact->id }}">
                                            {{ $contact->name }}
                                        </option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <!-- Invoice Date -->
                            <div>
                                <flux:input
                                    wire:model.live="invoice_date"
                                    type="date"
                                    label="Invoice Date"
                                    required
                                />
                            </div>

                            <!-- Payment Terms -->
                            <div>
                                <flux:input
                                    wire:model.live="payment_term_days"
                                    type="number"
                                    label="Payment Terms (days)"
                                    min="0"
                                    required
                                />
                            </div>

                            <!-- Due Date -->
                            <div>
                                <flux:input
                                    wire:model="due_date"
                                    type="date"
                                    label="Due Date"
                                    required
                                />
                            </div>

                            <!-- Tax Rate -->
                            <div>
                                <flux:input
                                    wire:model.live="tax_rate"
                                    type="number"
                                    step="0.01"
                                    label="Tax Rate (%)"
                                    min="0"
                                    max="100"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Items -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                                Items
                            </h2>
                            <flux:button
                                wire:click="addItem"
                                type="button"
                                variant="ghost"
                                icon="plus"
                            >
                                Add Item
                            </flux:button>
                        </div>

                        <div class="space-y-4">
                            @foreach($items as $index => $item)
                                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                                    <div class="flex items-start justify-between mb-3">
                                        <h3 class="text-sm font-medium text-zinc-900 dark:text-white">
                                            Item {{ $index + 1 }}
                                        </h3>
                                        @if(count($items) > 1)
                                            <flux:button
                                                wire:click="removeItem({{ $index }})"
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                icon="trash"
                                            />
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                                        <!-- Name -->
                                        <div class="md:col-span-6">
                                            <flux:input
                                                wire:model="items.{{ $index }}.name"
                                                type="text"
                                                placeholder="Item name"
                                                required
                                            />
                                        </div>

                                        <!-- Description -->
                                        <div class="md:col-span-6">
                                            <flux:textarea
                                                wire:model="items.{{ $index }}.description"
                                                placeholder="Description (optional)"
                                                rows="2"
                                            />
                                        </div>

                                        <!-- Quantity -->
                                        <div class="md:col-span-2">
                                            <flux:input
                                                wire:model.live="items.{{ $index }}.quantity"
                                                type="number"
                                                step="0.01"
                                                placeholder="Qty"
                                                min="0.01"
                                                required
                                            />
                                        </div>

                                        <!-- Unit -->
                                        <div class="md:col-span-1">
                                            <flux:input
                                                wire:model="items.{{ $index }}.unit"
                                                type="text"
                                                placeholder="Unit"
                                            />
                                        </div>

                                        <!-- Unit Price -->
                                        <div class="md:col-span-2">
                                            <flux:input
                                                wire:model.live="items.{{ $index }}.unit_price"
                                                type="number"
                                                step="0.01"
                                                placeholder="Unit Price"
                                                min="0"
                                                required
                                            />
                                        </div>

                                        <!-- Line Total -->
                                        <div class="md:col-span-1">
                                            <div class="text-right font-medium text-zinc-900 dark:text-white py-2">
                                                {{ $this->formatMoney($this->getItemLineTotal($item)) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Additional Information
                        </h2>

                        <div class="space-y-4">
                            <flux:textarea
                                wire:model="notes"
                                label="Internal Notes"
                                placeholder="Notes (not visible to customer)"
                                rows="3"
                            />

                            <flux:textarea
                                wire:model="terms"
                                label="Terms & Conditions"
                                placeholder="Payment terms and conditions"
                                rows="3"
                            />

                            <flux:textarea
                                wire:model="footer"
                                label="Footer"
                                placeholder="Footer text"
                                rows="2"
                            />
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Totals -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Totals
                        </h2>

                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    CHF {{ $this->formatMoney($this->invoiceTotals['subtotal']) }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">
                                    Tax ({{ number_format($tax_rate, 2) }}%)
                                </span>
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    CHF {{ $this->formatMoney($this->invoiceTotals['tax_amount']) }}
                                </span>
                            </div>

                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 mt-2">
                                <div class="flex justify-between">
                                    <span class="text-base font-semibold text-zinc-900 dark:text-white">
                                        Total
                                    </span>
                                    <span class="text-lg font-bold text-zinc-900 dark:text-white">
                                        CHF {{ $this->formatMoney($this->invoiceTotals['total']) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Actions
                        </h2>

                        <div class="space-y-3">
                            <flux:button
                                type="submit"
                                variant="primary"
                                class="w-full"
                            >
                                Update Invoice
                            </flux:button>

                            <flux:button
                                href="{{ route('invoices.show', $invoice) }}"
                                variant="ghost"
                                class="w-full"
                                wire:navigate
                            >
                                Cancel
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
