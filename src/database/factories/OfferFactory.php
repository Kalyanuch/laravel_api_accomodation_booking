<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Offer> */
class OfferFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 week', '+5 months');
        $checkOut = (clone $checkIn)->modify('+'.fake()->numberBetween(1, 14).' days');

        return [
            'supplier_id' => Supplier::factory(),
            'property_id' => Property::factory(),
            'import_id' => fn (array $attributes): Import => Import::factory()->create([
                'supplier_id' => $attributes['supplier_id'],
            ]),
            'external_id' => fake()->unique()->bothify('offer-########'),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'max_guests' => fake()->randomElement([1, 2, 2, 3, 4, 4, 6, 8]),
            'price' => fake()->numberBetween(5500, 95000),
            'currency' => fake()->randomElement(['EUR', 'EUR', 'EUR', 'GBP', 'USD', 'UAH']),
            'available_units' => fake()->numberBetween(0, 8),
            'expires_at' => fake()->dateTimeBetween('now', $checkIn),
        ];
    }

    public function available(): static
    {
        return $this->state(fn (): array => [
            'available_units' => fake()->numberBetween(1, 8),
            'expires_at' => fake()->dateTimeBetween('+1 day', '+1 week'),
        ]);
    }

    public function soldOut(): static
    {
        return $this->state(fn (): array => ['available_units' => 0]);
    }
}
