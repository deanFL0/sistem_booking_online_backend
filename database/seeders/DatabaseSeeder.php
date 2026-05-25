<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use App\Models\OperationalHour;
use App\Models\Resource;
use App\Models\ResourceAvailabilityOverride;
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

        // Create 3 resources (barbers)
        Service::factory(3)->create();

        // Create 5 services with their related data
        Resource::factory(5)->create()->each(function ($resource) {
            // Create operational hours for each day of the week
            for ($day = 0; $day < 7; $day++) {
                OperationalHour::factory()->create([
                    'resource_id' => $resource->id,
                    'day_of_week' => $day,
                ]);
            }

            // Create some resource availability overrides
            ResourceAvailabilityOverride::factory(3)->create();

            // Create bookings
            Booking::factory(5)->create();
            
        });
    }
}

