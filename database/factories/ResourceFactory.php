<?php

namespace Database\Factories;

use App\Models\Resource;
use App\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resource>
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name . ' ' . $this->faker->numberBetween(1, 10),
            'resource_type_id' => ResourceType::factory(),
            'description' => $this->faker->sentence,
            'is_active' => $this->faker->boolean(90), // 90% chance to be available
        ];
    }
}
