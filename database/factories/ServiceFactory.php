<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'description' => $this->faker->sentence,
            'price' => $this->faker->numberBetween(50000, 500000),
            'pricing_type' => $this->faker->randomElement(['one_time', 'hourly']),
            'duration' => $this->faker->numberBetween(30, 180), // minutes
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
