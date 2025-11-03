<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class SwissInvoiceLayoutService
{
    public function __construct(
        protected InvoiceCalculationService $calculator,
        protected QrInvoiceGenerator $qrGenerator
    ) {}

    /**
     * Generate a Swiss-formatted invoice PDF
     */
    public function generatePdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['company', 'contact', 'items', 'creator']);

        $data = [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'contact' => $invoice->contact,
            'items' => $invoice->items,
            'totals' => $this->calculateTotals($invoice),
            'qrCode' => $this->qrGenerator->generate($invoice),
        ];

        return Pdf::loadView('invoices.pdf', $data)
            ->setPaper('a4', 'portrait');
    }

    /**
     * Calculate formatted totals for the invoice
     */
    protected function calculateTotals(Invoice $invoice): array
    {
        $subtotal = $invoice->subtotal_amount;
        $taxAmount = $invoice->tax_amount;
        $total = $invoice->total_amount;

        return [
            'subtotal' => $subtotal,
            'subtotal_formatted' => $this->calculator->formatMoney($subtotal, $invoice->currency),
            'tax_rate' => $invoice->tax_rate,
            'tax_amount' => $taxAmount,
            'tax_amount_formatted' => $this->calculator->formatMoney($taxAmount, $invoice->currency),
            'total' => $total,
            'total_formatted' => $this->calculator->formatMoney($total, $invoice->currency),
        ];
    }

    /**
     * Format address for Swiss standards
     */
    public function formatAddress(string $name, ?string $street, ?string $streetNumber, ?string $postalCode, ?string $city, ?string $country = 'CH'): string
    {
        $lines = [$name];

        if ($street) {
            $addressLine = $street;
            if ($streetNumber) {
                $addressLine .= ' '.$streetNumber;
            }
            $lines[] = $addressLine;
        }

        if ($postalCode && $city) {
            $lines[] = $postalCode.' '.$city;
        } elseif ($city) {
            $lines[] = $city;
        }

        if ($country && $country !== 'CH') {
            $lines[] = strtoupper($country);
        }

        return implode("\n", array_filter($lines));
    }

    /**
     * Get Swiss standard date format
     */
    public function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('d.m.Y');
    }
}
