<?php

namespace Database\Factories;

use App\Enums\MechanicStatus;
use App\Models\Mechanic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mechanic>
 */
class MechanicFactory extends Factory
{
    protected $model = Mechanic::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->mekanik(),
            'name' => fake()->name(),
            'phone' => '08'.fake()->numerify('##########'),
            'specialty' => 'Mesin, servis rutin',
            'status' => MechanicStatus::Tersedia,
        ];
    }

    public function libur(): static
    {
        return $this->state(fn () => ['status' => MechanicStatus::Libur]);
    }

    public function sibuk(): static
    {
        return $this->state(fn () => ['status' => MechanicStatus::Sibuk]);
    }
}
