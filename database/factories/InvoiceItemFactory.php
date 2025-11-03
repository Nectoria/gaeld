<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $unitPrice = $this->faker->numberBetween(5000, 50000); // in cents
        $discountPercent = $this->faker->optional(0.3)->randomFloat(2, 0, 20);

        $subtotal = (int) ($quantity * $unitPrice);

        if ($discountPercent) {
            $discountAmount = (int) ($subtotal * ($discountPercent / 100));
            $subtotal -= $discountAmount;
        } else {
            $discountAmount = 0;
        }

        $taxAmount = (int) ($subtotal * 0.081); // 8.1% VAT
        $total = $subtotal + $taxAmount;

        return [
            'invoice_id' => Invoice::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'sku' => $this->faker->optional()->regexify('[A-Z]{3}-[0-9]{4}'),
            'quantity' => $quantity,
            'unit' => $this->faker->randomElement(['pcs', 'hrs', 'days', 'kg', 'm']),
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'tax_rate' => 8.1,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'discount_percent' => $discountPercent ?? 0,
            'discount_amount' => $discountAmount,
            'sort_order' => 0,
        ];
    }
}
