<?php

namespace Database\Factories;

use App\Models\OperationalHour;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationalHour>
 */
class OperationalHourFactory extends Factory
{
    protected $model = OperationalHour::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            // ensure a service exists; if not, create one
            'service_id' => Service::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'open_time' => $this->faker->dateTimeBetween('06:00:00', '10:00:00')->format('H:i:s'),
            'close_time' => $this->faker->dateTimeBetween('14:00:00', '22:00:00')->format('H:i:s'),
            'is_closed' => $this->faker->boolean(10),
        ];
    }
}
