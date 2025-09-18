<?php

namespace Database\Factories;

use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExerciseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Exercise::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word . ' Exercise',
            'description' => $this->faker->sentence,
            'video_url' => $this->faker->url,
            'body_part' => $this->faker->randomElement(['chest', 'back', 'legs', 'shoulders', 'arms', 'abs']),
            'equipment_needed' => $this->faker->randomElement(['none', 'dumbbell', 'barbell', 'machine']),
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'type' => $this->faker->randomElement(['strength', 'cardio', 'flexibility']),
            'target_muscles' => $this->faker->words(2, true),
            'is_public' => $this->faker->boolean,
        ];
    }
}
