<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Create a new invoice with items
     */
    public function createInvoice(Company $company, int $userId, array $data): Invoice
    {
        return DB::transaction(function () use ($company, $userId, $data) {
            // Generate invoice number
            $invoiceNumber = Invoice::generateInvoiceNumber($company->id);

            // Create invoice
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'contact_id' => $data['contact_id'],
                'created_by' => $userId,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'currency' => $company->currency,
                'tax_rate' => $data['tax_rate'],
                'tax_inclusive' => $data['tax_inclusive'] ?? false,
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'footer' => $data['footer'] ?? null,
                'qr_iban' => $company->iban,
                'subtotal_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
            ]);

            // Create invoice items
            if (! empty($data['items'])) {
                foreach ($data['items'] as $index => $itemData) {
                    if (empty($itemData['name'])) {
                        continue;
                    }

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'name' => $itemData['name'],
                        'description' => $itemData['description'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'] ?? 'pcs',
                        'unit_price' => (int) ($itemData['unit_price'] * 100),
                        'tax_rate' => $itemData['tax_rate'] ?? $data['tax_rate'],
                        'discount_percent' => $itemData['discount_percent'] ?? 0,
                        'sort_order' => $index,
                    ]);
                }
            }

            // Refresh to get calculated totals from model events
            $invoice->refresh();

            // Generate QR reference if IBAN is available
            if ($invoice->qr_iban) {
                $invoice->update([
                    'qr_reference' => $invoice->generateQrReference(),
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Update an existing invoice
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            // Update invoice
            $invoice->update([
                'contact_id' => $data['contact_id'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'tax_rate' => $data['tax_rate'],
                'tax_inclusive' => $data['tax_inclusive'] ?? false,
                'status' => $data['status'] ?? $invoice->status,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'footer' => $data['footer'] ?? null,
            ]);

            // Delete existing items
            $invoice->items()->delete();

            // Create new items
            if (! empty($data['items'])) {
                foreach ($data['items'] as $index => $itemData) {
                    if (empty($itemData['name'])) {
                        continue;
                    }

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'name' => $itemData['name'],
                        'description' => $itemData['description'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'] ?? 'pcs',
                        'unit_price' => (int) ($itemData['unit_price'] * 100),
                        'tax_rate' => $itemData['tax_rate'] ?? $data['tax_rate'],
                        'discount_percent' => $itemData['discount_percent'] ?? 0,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $invoice->refresh();
        });
    }

    /**
     * Calculate invoice totals from items using Money objects
     */
    public function calculateTotals(array $items, float $taxRate, string $currency = 'CHF'): array
    {
        return app(InvoiceCalculationService::class)->calculateTotals($items, $taxRate, $currency);
    }

    /**
     * Calculate totals for a single item using Money objects
     */
    public function calculateItemTotal(array $item, ?float $taxRate = null, string $currency = 'CHF'): array
    {
        return app(InvoiceCalculationService::class)->calculateItemTotal($item, $taxRate, $currency);
    }
}
