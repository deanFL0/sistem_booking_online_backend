<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use App\Models\OperationalHour;
use App\Models\HourlyQuota;
use App\Models\DateQuotaOverride;
use App\Models\Booking;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user and admin
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('123'),
        ]);
        User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin123'),
        ]);

        // Create 5 services with their related data
        Service::factory(5)->create()->each(function ($service) {
            // Create operational hours for each day of the week
            for ($day = 0; $day < 7; $day++) {
                OperationalHour::factory()->create([
                    'service_id' => $service->id,
                    'day_of_week' => $day,
                ]);

                // Create hourly quotas for each day
                HourlyQuota::factory()->create([
                    'service_id' => $service->id,
                    'day_of_week' => $day,
                ]);
            }

            // Create some date quota overrides
            DateQuotaOverride::factory(3)->create([
                'service_id' => $service->id,
            ]);

            // Create bookings for each service
            Booking::factory(5)->create([
                'service_id' => $service->id,
            ]);
        });
    }
}

