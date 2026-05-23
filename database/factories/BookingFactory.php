<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
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
        $service = Service::factory();
        $bookingTime = $this->faker->time('H:i:s');
        $durationMinutes = $this->faker->numberBetween(30, 180);
        $bookingDateTime = \DateTime::createFromFormat('H:i:s', $bookingTime);
        $bookingEndDateTime = (clone $bookingDateTime)->add(new \DateInterval('PT' . $durationMinutes . 'M'));
        
        return [
            'service_id' => $service,
            'user_id' => User::factory(),
            'customer_name' => $this->faker->name,
            'customer_email' => $this->faker->unique()->safeEmail,
            'customer_phone' => $this->faker->phoneNumber,
            'booking_date' => $this->faker->date,
            'booking_time' => $bookingTime,
            'duration_minutes' => $durationMinutes,
            'booking_end_time' => $bookingEndDateTime->format('H:i:s'),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
            'total_price' => $this->faker->numberBetween(100000, 500000),
        ];
    }
}
