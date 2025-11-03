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
     * Calculate invoice totals from items
     */
    public function calculateTotals(array $items, float $taxRate): array
    {
        $subtotal = 0;
        $taxAmount = 0;
        $total = 0;

        foreach ($items as $item) {
            if (empty($item['name'])) {
                continue;
            }

            $itemTotals = $this->calculateItemTotal($item, $taxRate);
            $subtotal += $itemTotals['subtotal'];
            $taxAmount += $itemTotals['tax_amount'];
            $total += $itemTotals['total'];
        }

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    /**
     * Calculate totals for a single item
     */
    public function calculateItemTotal(array $item, ?float $taxRate = null): array
    {
        $unitPriceCents = (int) (($item['unit_price'] ?? 0) * 100);
        $quantity = (float) ($item['quantity'] ?? 1);
        $itemTaxRate = $taxRate ?? ($item['tax_rate'] ?? 0);

        // Calculate subtotal
        $subtotal = (int) ($quantity * $unitPriceCents);

        // Apply discount
        $discountAmount = 0;
        if (isset($item['discount_percent']) && $item['discount_percent'] > 0) {
            $discountAmount = (int) ($subtotal * ($item['discount_percent'] / 100));
            $subtotal -= $discountAmount;
        }

        // Calculate tax
        $taxAmount = 0;
        if ($itemTaxRate > 0) {
            $taxAmount = (int) ($subtotal * ($itemTaxRate / 100));
        }

        $total = $subtotal + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'discount_amount' => $discountAmount,
        ];
    }
}
