<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ExerciseSeeder::class,
            WorkoutPlansSeeder::class,
            WorkoutTemplateDetailsSeeder::class,
            AlimentSeeder::class,
        ]);
    }
}
