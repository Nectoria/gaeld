<?php

namespace App\Services;

use Money\Currency;
use Money\Money;

class InvoiceCalculationService
{
    /**
     * Calculate invoice totals from items using Money objects
     */
    public function calculateTotals(array $items, float $taxRate, string $currency = 'CHF'): array
    {
        $currencyObj = new Currency($currency);
        $subtotal = new Money(0, $currencyObj);
        $taxAmount = new Money(0, $currencyObj);
        $total = new Money(0, $currencyObj);

        foreach ($items as $item) {
            if (empty($item['name'])) {
                continue;
            }

            $itemTotals = $this->calculateItemTotal($item, $taxRate, $currency);
            $subtotal = $subtotal->add($itemTotals['subtotal_money']);
            $taxAmount = $taxAmount->add($itemTotals['tax_amount_money']);
            $total = $total->add($itemTotals['total_money']);
        }

        return [
            'subtotal' => (int) $subtotal->getAmount(),
            'tax_amount' => (int) $taxAmount->getAmount(),
            'total' => (int) $total->getAmount(),
            'subtotal_money' => $subtotal,
            'tax_amount_money' => $taxAmount,
            'total_money' => $total,
        ];
    }

    /**
     * Calculate totals for a single item using Money objects
     */
    public function calculateItemTotal(array $item, ?float $taxRate = null, string $currency = 'CHF'): array
    {
        $currencyObj = new Currency($currency);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $quantity = (float) ($item['quantity'] ?? 1);
        $itemTaxRate = $taxRate ?? ($item['tax_rate'] ?? 0);
        $discountPercent = (float) ($item['discount_percent'] ?? 0);

        // Convert unit price to cents and create Money object
        $unitPriceMoney = new Money((int) ($unitPrice * 100), $currencyObj);

        // Calculate line subtotal (unit price × quantity)
        $subtotal = $unitPriceMoney->multiply((string) $quantity);

        // Apply discount if any
        $discountAmount = new Money(0, $currencyObj);
        if ($discountPercent > 0) {
            // Calculate discount: subtotal × (discount% / 100)
            $discountAmount = $subtotal->multiply((string) ($discountPercent / 100));
            $subtotal = $subtotal->subtract($discountAmount);
        }

        // Calculate tax amount
        $taxAmount = new Money(0, $currencyObj);
        if ($itemTaxRate > 0) {
            // Tax = subtotal × (tax_rate / 100)
            $taxAmount = $subtotal->multiply((string) ($itemTaxRate / 100));
        }

        // Calculate total
        $total = $subtotal->add($taxAmount);

        return [
            'subtotal' => (int) $subtotal->getAmount(),
            'tax_amount' => (int) $taxAmount->getAmount(),
            'total' => (int) $total->getAmount(),
            'discount_amount' => (int) $discountAmount->getAmount(),
            'subtotal_money' => $subtotal,
            'tax_amount_money' => $taxAmount,
            'total_money' => $total,
            'discount_amount_money' => $discountAmount,
        ];
    }

    /**
     * Calculate line total for display (quantity × unit price before tax/discount)
     */
    public function calculateLineTotal(array $item, string $currency = 'CHF'): int
    {
        $currencyObj = new Currency($currency);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $quantity = (float) ($item['quantity'] ?? 1);

        $unitPriceMoney = new Money((int) ($unitPrice * 100), $currencyObj);
        $lineTotal = $unitPriceMoney->multiply((string) $quantity);

        return (int) $lineTotal->getAmount();
    }

    /**
     * Format money amount in cents to a display string
     */
    public function formatMoney(int $cents, string $currency = 'CHF'): string
    {
        $money = new Money($cents, new Currency($currency));

        return number_format((int) $money->getAmount() / 100, 2, '.', '\'');
    }

    /**
     * Calculate due date based on invoice date and payment terms
     */
    public function calculateDueDate(string $invoiceDate, int $paymentTermDays): string
    {
        return now()
            ->parse($invoiceDate)
            ->addDays($paymentTermDays)
            ->format('Y-m-d');
    }
}
