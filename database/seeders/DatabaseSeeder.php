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
        /*
        |--------------------------------------------------------------------------
        | OLD FACTORY-BASED SEEDER (Commented out for manual testing data)
        |--------------------------------------------------------------------------
        |
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
        */

        /*
        |--------------------------------------------------------------------------
        | MANUAL TEST DATA FOR BOOKING ENDPOINT TESTING
        |--------------------------------------------------------------------------
        */

        // Create test users
        $testUser = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);

        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '08987654321',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
        ]);

        // Create resource types
        $chairType = ResourceType::create([
            'name' => 'Barber Chair',
            'description' => 'Professional barber chair for haircuts and styling',
        ]);

        $washType = ResourceType::create([
            'name' => 'Wash Station',
            'description' => 'Hair washing and treatment station',
        ]);

        // Create resources (barbers/chairs)
        $chair1 = Resource::create([
            'name' => 'Chair 1',
            'resource_type_id' => $chairType->id,
            'description' => 'Main barber chair',
            'is_active' => true,
        ]);

        $chair2 = Resource::create([
            'name' => 'Chair 2',
            'resource_type_id' => $chairType->id,
            'description' => 'Secondary barber chair',
            'is_active' => true,
        ]);

        $wash1 = Resource::create([
            'name' => 'Wash Station 1',
            'resource_type_id' => $washType->id,
            'description' => 'Hair washing station',
            'is_active' => true,
        ]);

        // Create operational hours for each resource (9 AM - 5 PM, Monday to Friday)
        foreach ([$chair1, $chair2, $wash1] as $resource) {
            // Monday to Friday (0-4)
            for ($day = 1; $day <= 5; $day++) {
                OperationalHour::create([
                    'resource_id' => $resource->id,
                    'day_of_week' => $day,
                    'open_time' => '09:00:00',
                    'close_time' => '17:00:00',
                    'is_closed' => false,
                ]);
            }

            // Saturday (6) - 9 AM to 2 PM
            OperationalHour::create([
                'resource_id' => $resource->id,
                'day_of_week' => 6,
                'open_time' => '09:00:00',
                'close_time' => '14:00:00',
                'is_closed' => false,
            ]);

            // Sunday (0) - Closed
            OperationalHour::create([
                'resource_id' => $resource->id,
                'day_of_week' => 0,
                'open_time' => '00:00:00',
                'close_time' => '00:00:00',
                'is_closed' => true,
            ]);
        }

        // Create services
        $haircut = Service::create([
            'name' => 'Haircut',
            'description' => 'Professional haircut service',
            'price' => 50000,
            'pricing_type' => 'one_time',
            'duration' => 30,
            'is_active' => true,
        ]);

        $coloring = Service::create([
            'name' => 'Hair Coloring',
            'description' => 'Professional hair coloring service',
            'price' => 150000,
            'pricing_type' => 'hourly',
            'duration' => 90,
            'is_active' => true,
        ]);

        $styling = Service::create([
            'name' => 'Hair Styling',
            'description' => 'Professional hair styling service',
            'price' => 75000,
            'pricing_type' => 'one_time',
            'duration' => 45,
            'is_active' => true,
        ]);

        // Link services to resource types
        // Haircut needs 1 chair
        $haircut->resourceTypes()->attach($chairType->id, ['quantity' => 1]);

        // Hair Coloring needs 1 chair and 1 wash station
        $coloring->resourceTypes()->attach($chairType->id, ['quantity' => 1]);
        $coloring->resourceTypes()->attach($washType->id, ['quantity' => 1]);

        // Hair Styling needs 1 chair
        $styling->resourceTypes()->attach($chairType->id, ['quantity' => 1]);

        // Create settings
        Setting::create([
            'key' => 'min_cancellation_hours',
            'value' => '24',
        ]);
    }
}

