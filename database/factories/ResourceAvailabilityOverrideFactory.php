<?php

namespace Database\Factories;

use App\Models\ResourceAvailabilityOverride;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceAvailabilityOverride>
 */
class ResourceAvailabilityOverrideFactory extends Factory
{
    protected $model = ResourceAvailabilityOverride::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(6, 12);
        $endHour = $this->faker->numberBetween($startHour + 1, 18);

        return [
            'resource_id' => Resource::factory(),
            'date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $endHour),
            'status' => $this->faker->randomElement(['available', 'unavailable']),
        ];
    }
}
