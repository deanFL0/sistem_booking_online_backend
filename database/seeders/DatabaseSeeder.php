<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use App\Models\OperationalHour;
use App\Models\Resource;
use App\Models\ResourceAvailabilityOverride;
use App\Models\Booking;
use App\Models\ResourceType;
use App\Models\Setting;
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

        // Create 3 services
        Service::factory(3)->create();

        // Create 3 resource types
        $resourceTypes = ResourceType::factory(3)->create();

        // Link services to resource types
        Service::all()->each(function ($service) use ($resourceTypes) {
            $resourceTypes->random(2)->each(function ($resourceType) use ($service) {
                $resourceType->services()->attach($service->id, ['quantity' => rand(1, 3)]);
            });
        });

        // Create 10 resources linked to resource types with operational hours
        Resource::factory(10)->create()->each(function ($resource) {
            // Assign a random resource type
            $resource->update(['resource_type_id' => ResourceType::inRandomOrder()->first()->id]);
            
            // Create operational hours for each day of the week
            for ($day = 0; $day < 7; $day++) {
                OperationalHour::factory()->create([
                    'resource_id' => $resource->id,
                    'day_of_week' => $day,
                ]);
            }
        });

        // Create some resource availability overrides
        ResourceAvailabilityOverride::factory(3)->create();
        
        // Create bookings
        Booking::factory(5)->create();
        
        Setting::factory()->create([
            'key' => 'min_cancellation_hours',
            'value' => '24',
        ]);
    }
}

