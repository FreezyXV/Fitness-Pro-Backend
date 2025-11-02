<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Goal;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SeedController extends Controller
{
    public function runMigrations()
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            return response()->json([
                'success' => true,
                'message' => 'Migrations ran successfully',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run migrations: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function testVideoInsert()
    {
        try {
            $exercise = Exercise::create([
                'name' => 'Test Exercise',
                'body_part' => 'chest',
                'category' => 'strength',
                'muscle_groups' => ['chest'],
                'difficulty' => 'beginner',
                'instructions' => ['Test instruction'],
                'duration' => 1,
                'video_url' => 'https://www.youtube.com/watch?v=test123',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test exercise created successfully',
                'exercise' => $exercise,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test exercise: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function clearWorkouts()
    {
        try {
            Workout::truncate();
            return response()->json([
                'success' => true,
                'message' => 'All workouts cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear workouts: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function clearExercises()
    {
        try {
            Exercise::truncate();
            return response()->json([
                'success' => true,
                'message' => 'All exercises cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear exercises: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function seedWorkoutsPortfolio()
    {
        try {
            if (Workout::count() > 0) {
                return response()->json(['success' => true, 'message' => 'Workout demo data already exists', 'status' => 'already_seeded']);
            }

            $exercises = Exercise::take(12)->get();
            if ($exercises->count() < 3) {
                return response()->json(['success' => false, 'message' => 'Not enough exercises available to seed workouts.'], 400);
            }

            $workoutTemplates = [
                [
                    'name' => 'Entraînement Force Débutant',
                    'description' => 'Programme de musculation parfait pour commencer',
                    'actual_duration' => 45,
                    'actual_calories' => 250,
                    'is_template' => true,
                    'user_id' => 1, // System user
                ],
                 [
                    'name' => 'HIIT Cardio Intense',
                    'description' => 'Entraînement cardio haute intensité',
                    'actual_duration' => 30,
                    'actual_calories' => 350,
                    'is_template' => true,
                    'user_id' => 1,
                ],
            ];

            foreach ($workoutTemplates as $templateData) {
                Workout::create($templateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Workout portfolio demo data seeded successfully',
                'workouts_created' => count($workoutTemplates),
                'status' => 'freshly_seeded',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to seed workout portfolio demo data: ' . $e->getMessage()], 500);
        }
    }

    public function seedGoals()
    {
        try {
            $user = User::first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No users found in database'], 400);
            }

            $goalsData = [
                [
                    'user_id' => $user->id,
                    'title' => "Perdre 10 kg pour l'été",
                    'description' => "Atteindre un poids plus sain.",
                    'category' => 'weight', 'unit' => 'kg', 'current_value' => 2, 'target_value' => 10,
                    'target_date' => now()->addDays(60)->format('Y-m-d'), 'status' => 'active',
                ],
                [
                    'user_id' => $user->id,
                    'title' => 'Courir 150 km ce mois',
                    'description' => 'Améliorer mon endurance cardiovasculaire.',
                    'category' => 'cardio', 'unit' => 'km', 'current_value' => 120, 'target_value' => 150,
                    'target_date' => now()->endOfMonth()->format('Y-m-d'), 'status' => 'active',
                ],
            ];

            foreach ($goalsData as $goalData) {
                Goal::firstOrCreate(['title' => $goalData['title'], 'user_id' => $user->id], $goalData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Goals seeded directly',
                'goals_created' => count($goalsData),
                'status' => 'seeded',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to seed goals: ' . $e->getMessage()], 500);
        }
    }

    public function seedPortfolio()
    {
        try {
            if (Exercise::count() > 0) {
                return response()->json(['success' => true, 'message' => 'Portfolio demo data already exists', 'status' => 'already_seeded']);
            }

            $exercises = [
                ['name' => 'Push-ups', 'body_part' => 'chest', 'category' => 'strength', 'muscle_groups' => ['chest', 'triceps'], 'difficulty' => 'beginner', 'instructions' => ['Start in plank position'], 'duration' => 1, 'video_url' => 'https://www.youtube.com/watch?v=IODxDxX7oi4'],
                ['name' => 'Squats', 'body_part' => 'legs', 'category' => 'strength', 'muscle_groups' => ['quadriceps', 'glutes'], 'difficulty' => 'beginner', 'instructions' => ['Stand with feet shoulder-width apart'], 'duration' => 1, 'video_url' => 'https://www.youtube.com/watch?v=aclHkVaku9U'],
                ['name' => 'Running', 'body_part' => 'cardio', 'category' => 'cardio', 'muscle_groups' => ['legs', 'core'], 'difficulty' => 'intermediate', 'instructions' => ['Maintain steady pace'], 'duration' => 30, 'video_url' => 'https://www.youtube.com/watch?v=brFHyOtTwH4'],
            ];

            foreach ($exercises as $exercise) {
                Exercise::create($exercise);
            }

            Artisan::call('db:seed', ['--class' => 'GoalsSeeder']);

            return response()->json([
                'success' => true,
                'message' => 'Portfolio demo data seeded successfully',
                'exercises_created' => count($exercises),
                'status' => 'freshly_seeded',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to seed portfolio demo data: ' . $e->getMessage()], 500);
        }
    }
}
