<?php

namespace Database\Factories;

use App\Models\DateQuotaOverride;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DateQuotaOverride>
 */
class DateQuotaOverrideFactory extends Factory
{
    protected $model = DateQuotaOverride::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(6, 12);
        $endHour = $this->faker->numberBetween($startHour + 1, 18);

        return [
            'service_id' => Service::factory(),
            'override_date' => $this->faker->date,
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $endHour),
            'custom_quota' => $this->faker->numberBetween(1, 3), // Reduced quota for special dates
            'is_closed' => $this->faker->boolean(20), // 20% chance to be closed
        ];
    }
}
