<?php

namespace Database\Factories;

use App\Models\HourlyQuota;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HourlyQuota>
 */
class HourlyQuotaFactory extends Factory
{
    protected $model = HourlyQuota::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(6, 12);
        $endHour = $this->faker->numberBetween($startHour + 1, 18);

        return [
            'service_id' => Service::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $endHour),
            'default_quota' => $this->faker->numberBetween(1, 5), // 1-5 concurrent bookings allowed
        ];
    }
}
