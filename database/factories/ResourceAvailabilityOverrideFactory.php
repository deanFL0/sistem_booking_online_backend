<?php

namespace Database\Factories;

use App\Models\Resource;
use App\Models\ResourceAvailabilityOverride;
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
        $date = $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d');
        $startHour = $this->faker->numberBetween(6, 12);
        $endHour = $this->faker->numberBetween($startHour + 1, 18);

        return [
            'resource_id' => Resource::factory(),
            'start_time' => sprintf('%s %02d:00:00', $date, $startHour),
            'end_time' => sprintf('%s %02d:00:00', $date, $endHour),
            'status' => $this->faker->randomElement(['available', 'unavailable']),
        ];
    }
}
