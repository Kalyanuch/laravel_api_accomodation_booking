<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Property> */
class PropertyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $city = fake()->randomElement([
            'Barcelona',
            'Berlin',
            'Kyiv',
            'Lisbon',
            'London',
            'Paris',
            'Prague',
            'Rome',
            'Vienna',
            'Warsaw',
        ]);

        $type = fake()->randomElement([
            'Apartment',
            'Boutique Hotel',
            'City Loft',
            'Guest House',
            'Residence',
            'Riverside Suites',
        ]);

        return [
            'code' => fake()->unique()->bothify('???-####'),
            'name' => sprintf('%s %s', fake()->lastName(), $type),
            'city' => $city,
        ];
    }
}
