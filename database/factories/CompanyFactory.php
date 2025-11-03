<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'legal_name' => fake()->optional()->company(),
            'vat_number' => fake()->regexify('CHE-[0-9]{3}\.[0-9]{3}\.[0-9]{3}'),
            'registration_number' => fake()->optional()->numerify('CHE-###.###.###'),
            'street' => fake()->streetName(),
            'street_number' => fake()->buildingNumber(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => 'CH',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->optional()->url(),
            'iban' => fake()->iban('CH'),
            'bank_name' => fake()->company().' Bank',
            'currency' => 'CHF',
            'locale' => 'de_CH',
            'timezone' => 'Europe/Zurich',
            'is_active' => true,
        ];
    }
}
