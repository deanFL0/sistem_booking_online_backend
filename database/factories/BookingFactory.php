<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $bookingTime = $this->faker->dateTimeBetween('+1 days', '+1 month')->format('Y-m-d H:i:s');
        $durationMinutes = $this->faker->numberBetween(30, 180);
        $bookingDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $bookingTime);
        $bookingEndDateTime = (clone $bookingDateTime)->add(new \DateInterval('PT' . $durationMinutes . 'M'));
        
        return [
            'user_id' => User::factory(),
            'service_id' => Service::factory(),
            'customer_name' => $this->faker->name,
            'customer_email' => $this->faker->unique()->safeEmail,
            'customer_phone' => $this->faker->phoneNumber,
            'start_datetime' => $bookingTime,
            'end_datetime' => $bookingEndDateTime->format('H:i:s'),
            'duration_minutes' => $durationMinutes,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
            'total_price' => $this->faker->numberBetween(100000, 500000),
        ];
    }
}
