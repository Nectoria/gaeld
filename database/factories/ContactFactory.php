<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => $this->faker->randomElement(['customer', 'vendor', 'both']),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->optional()->phoneNumber(),
            'website' => $this->faker->optional()->url(),
            'vat_number' => $this->faker->optional()->regexify('CHE-[0-9]{3}\.[0-9]{3}\.[0-9]{3}'),
            'tax_id' => $this->faker->optional()->numerify('########'),
            'street' => $this->faker->streetName(),
            'street_number' => $this->faker->buildingNumber(),
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'country' => 'CH',
            'iban' => $this->faker->optional()->iban('CH'),
            'bank_name' => $this->faker->optional()->company(),
            'notes' => $this->faker->optional()->text(),
            'reference_number' => $this->faker->optional()->numerify('REF-####'),
            'payment_term_days' => $this->faker->randomElement([7, 14, 30, 60, 90]),
            'currency' => 'CHF',
            'is_active' => true,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer',
        ]);
    }

    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'vendor',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
