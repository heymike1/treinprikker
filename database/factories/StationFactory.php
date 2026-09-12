<?php

namespace Database\Factories;

use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Station>
 */
class StationFactory extends Factory
{
    protected $model = Station::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->lexify('station ??????'));

        return [
            'code' => strtoupper(Str::random(4)),
            'uic' => (string) $this->faker->unique()->numberBetween(8400001, 8409999),
            'name' => $name,
            'slug' => Str::slug($name),
            'latitude' => $this->faker->randomFloat(6, 50.8, 53.4),
            'longitude' => $this->faker->randomFloat(6, 3.5, 7.0),
            'province' => $this->faker->randomElement(['Utrecht', 'Gelderland', 'Limburg', 'Groningen', 'Zuid-Holland']),
            'municipality' => $this->faker->city(),
            'station_type' => 'stoptreinstation',
            'active' => true,
            'difficulty_rating' => $this->faker->numberBetween(1, 100),
            'difficulty_source' => 'heuristic',
        ];
    }

    public function easy(): static
    {
        return $this->state(['difficulty_rating' => 15]);
    }

    public function medium(): static
    {
        return $this->state(['difficulty_rating' => 50]);
    }

    public function hard(): static
    {
        return $this->state(['difficulty_rating' => 85]);
    }
}
