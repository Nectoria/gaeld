<?php

use App\Services\InvoiceCalculationService;
use Money\Currency;
use Money\Money;

beforeEach(function () {
    $this->service = new InvoiceCalculationService();
});

test('calculateItemTotal calculates correct amounts', function () {
    $item = [
        'name' => 'Test Item',
        'quantity' => 2,
        'unit_price' => 100.00,
        'discount_percent' => 0,
    ];

    $result = $this->service->calculateItemTotal($item, 8.1, 'CHF');

    expect($result['subtotal'])->toBe(20000) // 200.00 CHF in cents
        ->and($result['tax_amount'])->toBe(1620) // 8.1% of 200.00
        ->and($result['total'])->toBe(21620); // 216.20 CHF in cents
});

test('calculateItemTotal applies discount correctly', function () {
    $item = [
        'name' => 'Test Item',
        'quantity' => 1,
        'unit_price' => 100.00,
        'discount_percent' => 10,
    ];

    $result = $this->service->calculateItemTotal($item, 8.1, 'CHF');

    expect($result['subtotal'])->toBe(9000) // 100.00 - 10% = 90.00
        ->and($result['discount_amount'])->toBe(1000) // 10.00
        ->and($result['tax_amount'])->toBe(729) // 8.1% of 90.00
        ->and($result['total'])->toBe(9729); // 97.29 CHF
});

test('calculateItemTotal handles fractional quantities', function () {
    $item = [
        'name' => 'Test Item',
        'quantity' => 2.5,
        'unit_price' => 50.00,
        'discount_percent' => 0,
    ];

    $result = $this->service->calculateItemTotal($item, 8.1, 'CHF');

    expect($result['subtotal'])->toBe(12500) // 125.00 CHF in cents
        ->and($result['tax_amount'])->toBe(1013) // 8.1% of 125.00 = 10.125, rounded to 10.13
        ->and($result['total'])->toBe(13513); // 135.13 CHF
});

test('calculateTotals sums multiple items correctly', function () {
    $items = [
        [
            'name' => 'Item 1',
            'quantity' => 1,
            'unit_price' => 100.00,
            'discount_percent' => 0,
        ],
        [
            'name' => 'Item 2',
            'quantity' => 2,
            'unit_price' => 50.00,
            'discount_percent' => 0,
        ],
    ];

    $result = $this->service->calculateTotals($items, 8.1, 'CHF');

    expect($result['subtotal'])->toBe(20000) // 200.00 CHF
        ->and($result['tax_amount'])->toBe(1620) // 8.1% of 200.00
        ->and($result['total'])->toBe(21620); // 216.20 CHF
});

test('calculateTotals returns Money objects', function () {
    $items = [
        [
            'name' => 'Item 1',
            'quantity' => 1,
            'unit_price' => 100.00,
            'discount_percent' => 0,
        ],
    ];

    $result = $this->service->calculateTotals($items, 8.1, 'CHF');

    expect($result['subtotal_money'])->toBeInstanceOf(Money::class)
        ->and($result['tax_amount_money'])->toBeInstanceOf(Money::class)
        ->and($result['total_money'])->toBeInstanceOf(Money::class);
});

test('calculateLineTotal calculates basic line total', function () {
    $item = [
        'quantity' => 3,
        'unit_price' => 50.00,
    ];

    $result = $this->service->calculateLineTotal($item, 'CHF');

    expect($result)->toBe(15000); // 150.00 CHF in cents
});

test('formatMoney formats cents to string correctly', function () {
    $result = $this->service->formatMoney(123456, 'CHF');

    expect($result)->toBe("1'234.56"); // Swiss number format
});

test('formatMoney handles zero correctly', function () {
    $result = $this->service->formatMoney(0, 'CHF');

    expect($result)->toBe('0.00');
});

test('calculateDueDate calculates correct due date', function () {
    $invoiceDate = '2024-01-15';
    $paymentTermDays = 30;

    $result = $this->service->calculateDueDate($invoiceDate, $paymentTermDays);

    expect($result)->toBe('2024-02-14');
});

test('calculateDueDate handles different payment terms', function () {
    $invoiceDate = '2024-01-01';

    expect($this->service->calculateDueDate($invoiceDate, 7))->toBe('2024-01-08')
        ->and($this->service->calculateDueDate($invoiceDate, 14))->toBe('2024-01-15')
        ->and($this->service->calculateDueDate($invoiceDate, 60))->toBe('2024-03-01');
});

test('calculateItemTotal with zero tax rate', function () {
    $item = [
        'name' => 'Test Item',
        'quantity' => 1,
        'unit_price' => 100.00,
        'discount_percent' => 0,
    ];

    $result = $this->service->calculateItemTotal($item, 0, 'CHF');

    expect($result['subtotal'])->toBe(10000)
        ->and($result['tax_amount'])->toBe(0)
        ->and($result['total'])->toBe(10000);
});

test('calculateTotals skips items without name', function () {
    $items = [
        [
            'name' => '',
            'quantity' => 1,
            'unit_price' => 100.00,
        ],
        [
            'name' => 'Valid Item',
            'quantity' => 1,
            'unit_price' => 50.00,
        ],
    ];

    $result = $this->service->calculateTotals($items, 8.1, 'CHF');

    expect($result['subtotal'])->toBe(5000); // Only the valid item
});

test('money calculations are precise with complex numbers', function () {
    $item = [
        'name' => 'Test Item',
        'quantity' => 3.33,
        'unit_price' => 99.99,
        'discount_percent' => 7.5,
    ];

    $result = $this->service->calculateItemTotal($item, 7.7, 'CHF');

    // This tests that we're using Money library correctly for precision
    expect($result['subtotal'])->toBeInt()
        ->and($result['tax_amount'])->toBeInt()
        ->and($result['total'])->toBeInt();
});
