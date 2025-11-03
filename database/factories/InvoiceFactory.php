<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $invoiceDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $dueDate = (clone $invoiceDate)->modify('+30 days');

        return [
            'company_id' => Company::factory(),
            'contact_id' => Contact::factory(),
            'created_by' => User::factory(),
            'invoice_number' => 'INV-'.now()->year.'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'reference_number' => $this->faker->optional()->numerify('REF-####'),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'paid_at' => null,
            'subtotal_amount' => $subtotal = $this->faker->numberBetween(10000, 100000), // in cents
            'tax_amount' => $taxAmount = (int) ($subtotal * 0.081), // 8.1% VAT
            'total_amount' => $subtotal + $taxAmount,
            'paid_amount' => 0,
            'currency' => 'CHF',
            'tax_rate' => 8.1,
            'tax_inclusive' => false,
            'status' => $this->faker->randomElement(['draft', 'sent', 'viewed', 'partial', 'paid', 'overdue', 'cancelled']),
            'payment_method' => null,
            'payment_reference' => null,
            'qr_reference' => $this->faker->optional()->numerify('## ##### ##### ##### ##### #####'),
            'qr_additional_info' => null,
            'qr_iban' => $this->faker->optional()->iban('CH'),
            'notes' => $this->faker->optional()->text(),
            'terms' => 'Payment due within 30 days.',
            'footer' => $this->faker->optional()->sentence(),
            'pdf_path' => null,
            'qr_code_path' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
            'paid_amount' => $attributes['total_amount'],
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => now()->subDays(10),
        ]);
    }
}
