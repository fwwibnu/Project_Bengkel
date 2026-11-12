<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'plate_number' => 'B '.fake()->unique()->numberBetween(1000, 9999).' '.fake()->lexify('???'),
            'brand' => fake()->randomElement(['Honda', 'Yamaha', 'Suzuki']),
            'model' => fake()->randomElement(['Vario 125', 'NMAX', 'Beat', 'Mio']),
            'type' => 'motor',
            'year' => fake()->numberBetween(2015, 2024),
        ];
    }
}
