<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workout;

class WorkoutTemplateDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = Workout::where('is_template', true)->get();

        foreach ($templates as $template) {
            $template->update([
                'body_focus' => 'full_body',
                'type' => 'strength',
                'intensity' => 'medium',
                'equipment' => 'none',
                'goal' => 'muscle_gain',
                'frequency' => 'thrice',
            ]);
        }
    }
}
