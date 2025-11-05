<?php

use App\Models\Contact;
use App\Models\Invoice;
use App\Services\InvoiceCalculationService;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    // Invoice fields
    public ?int $contact_id = null;
    public string $invoice_date = '';
    public int $payment_term_days = 30;
    public string $due_date = '';
    public float $tax_rate = 8.1; // Default Swiss VAT rate
    public bool $tax_inclusive = false;
    public string $notes = '';
    public string $terms = '';
    public string $footer = '';

    // Invoice items
    public array $items = [];

    public function mount(InvoiceCalculationService $calculationService): void
    {
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = $calculationService->calculateDueDate($this->invoice_date, $this->payment_term_days);
        $this->addItem();
        $this->terms = "Payment due within {$this->payment_term_days} days.";
    }

    #[Computed]
    public function contacts()
    {
        return Contact::customers()->active()->orderBy('name')->get();
    }

    public function updatedPaymentTermDays(InvoiceCalculationService $calculationService): void
    {
        $this->due_date = $calculationService->calculateDueDate($this->invoice_date, $this->payment_term_days);
        $this->terms = "Payment due within {$this->payment_term_days} days.";
    }

    public function updatedInvoiceDate(InvoiceCalculationService $calculationService): void
    {
        $this->due_date = $calculationService->calculateDueDate($this->invoice_date, $this->payment_term_days);
    }

    public function updatedContactId(InvoiceCalculationService $calculationService): void
    {
        if ($this->contact_id) {
            // Global scope automatically filters by current company, but using findOrFail for explicit validation
            try {
                $contact = Contact::findOrFail($this->contact_id);
                $this->payment_term_days = $contact->payment_term_days;
                $this->due_date = $calculationService->calculateDueDate($this->invoice_date, $this->payment_term_days);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                // Contact not found or doesn't belong to current company
                $this->contact_id = null;
                $this->addError('contact_id', 'Selected contact is not valid.');
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
        return app(InvoiceCalculationService::class)->calculateTotals(
            $this->items,
            $this->tax_rate,
            currentCompany()->currency
        );
    }

    public function formatMoney(int $cents): string
    {
        return app(InvoiceCalculationService::class)->formatMoney($cents, currentCompany()->currency);
    }

    public function getItemLineTotal(array $item): int
    {
        return app(InvoiceCalculationService::class)->calculateLineTotal($item, currentCompany()->currency);
    }

    public function save(bool $draft = true): void
    {
        $this->authorize('create', Invoice::class);

        $validated = $this->validate([
            'contact_id' => 'required|exists:contacts,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $invoice = app(InvoiceService::class)->createInvoice(
            currentCompany(),
            Auth::id(),
            [
                'contact_id' => $this->contact_id,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'tax_rate' => $this->tax_rate,
                'tax_inclusive' => $this->tax_inclusive,
                'status' => $draft ? 'draft' : 'sent',
                'notes' => $this->notes,
                'terms' => $this->terms,
                'footer' => $this->footer,
                'items' => $this->items,
            ]
        );

        $this->redirect(route('invoices.show', $invoice), navigate: true);
    }

    public function saveAsDraft(): void
    {
        $this->save(draft: true);
    }

    public function saveAndSend(): void
    {
        $this->save(draft: false);
    }
}; ?>

<div>
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ __('Create Invoice') }}</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Create a new invoice for your customer') }}
                    </p>
                </div>
                <flux:button href="{{ route('invoices.index') }}" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>

        <form wire:submit="saveAsDraft">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            {{ __('Invoice Details') }}
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Customer -->
                            <div class="md:col-span-2">
                                <flux:select
                                    wire:model.live="contact_id"
                                    :label="__('Customer')"
                                    :placeholder="__('Select a customer')"
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
                                    :label="__('Invoice Date')"
                                    required
                                />
                            </div>

                            <!-- Payment Terms -->
                            <div>
                                <flux:input
                                    wire:model.live="payment_term_days"
                                    type="number"
                                    :label="__('Payment Terms (days)')"
                                    min="0"
                                    required
                                />
                            </div>

                            <!-- Due Date -->
                            <div>
                                <flux:input
                                    wire:model="due_date"
                                    type="date"
                                    :label="__('Due Date')"
                                    required
                                />
                            </div>

                            <!-- Tax Rate -->
                            <div>
                                <flux:input
                                    wire:model.live="tax_rate"
                                    type="number"
                                    step="0.01"
                                    :label="__('Tax Rate (%)')"
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
                                {{ __('Items') }}
                            </h2>
                            <flux:button
                                wire:click="addItem"
                                type="button"
                                variant="ghost"
                                icon="plus"
                            >
                                {{ __('Add Item') }}
                            </flux:button>
                        </div>

                        <div class="space-y-4">
                            @foreach($items as $index => $item)
                                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                                    <div class="flex items-start justify-between mb-3">
                                        <h3 class="text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ __('Item') }} {{ $index + 1 }}
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
                                                :placeholder="__('Description (optional)')"
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
                            {{ __('Additional Information') }}
                        </h2>

                        <div class="space-y-4">
                            <flux:textarea
                                wire:model="notes"
                                :label="__('Internal Notes')"
                                :placeholder="__('Notes (not visible to customer)')"
                                rows="3"
                            />

                            <flux:textarea
                                wire:model="terms"
                                :label="__('Terms & Conditions')"
                                :placeholder="__('Payment terms and conditions')"
                                rows="3"
                            />

                            <flux:textarea
                                wire:model="footer"
                                :label="__('Footer')"
                                :placeholder="__('Footer text')"
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
                            {{ __('Totals') }}
                        </h2>

                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">{{ __('Subtotal') }}</span>
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    CHF {{ $this->formatMoney($this->invoiceTotals['subtotal']) }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">
                                    {{ __('Tax') }} ({{ number_format($tax_rate, 2) }}%)
                                </span>
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    CHF {{ $this->formatMoney($this->invoiceTotals['tax_amount']) }}
                                </span>
                            </div>

                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 mt-2">
                                <div class="flex justify-between">
                                    <span class="text-base font-semibold text-zinc-900 dark:text-white">
                                        {{ __('Total') }}
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
                            {{ __('Actions') }}
                        </h2>

                        <div class="space-y-3">
                            <flux:button
                                type="submit"
                                variant="primary"
                                class="w-full"
                            >
                                {{ __('Save as Draft') }}
                            </flux:button>

                            <flux:button
                                wire:click="saveAndSend"
                                type="button"
                                class="w-full"
                            >
                                {{ __('Save & Send') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
</div>
