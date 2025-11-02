<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Workout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Debug controller for workout-related debugging operations
 * Only accessible when APP_DEBUG=true
 */
class WorkoutDebugController extends BaseController
{
    public function __construct()
    {
        // Only allow access in debug mode
        if (!config('app.debug')) {
            abort(404, 'Debug endpoints are disabled in production');
        }
    }

    /**
     * Debug workout templates - Enhanced for better debugging
     */
    public function debugTemplates(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            // Get all workouts for this user
            $allWorkouts = Workout::where('user_id', $user->id)->get();
            $templates = Workout::where('is_template', true)->where('user_id', $user->id)->with('exercises')->get();
            $sessions = Workout::where('is_template', false)->where('user_id', $user->id)->get();

            // Check exercise counts
            $exercises = \App\Models\Exercise::count();

            // Get other users' templates for comparison
            $otherUsersTemplates = Workout::where('is_template', true)
                                        ->where('user_id', '!=', $user->id)
                                        ->take(5)
                                        ->get();

            $debugInfo = [
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'database_counts' => [
                    'total_workouts' => $allWorkouts->count(),
                    'user_templates' => $templates->count(),
                    'user_sessions' => $sessions->count(),
                    'total_exercises' => $exercises,
                    'other_users_templates' => $otherUsersTemplates->count()
                ],
                'user_workouts_summary' => $allWorkouts->map(function($w) {
                    return [
                        'id' => $w->id,
                        'name' => $w->name,
                        'is_template' => $w->is_template,
                        'user_id' => $w->user_id,
                        'category' => $w->category,
                        'difficulty_level' => $w->difficulty_level,
                        'exercise_count' => $w->exercises()->count(),
                        'created_at' => $w->created_at->toISOString()
                    ];
                })->toArray(),
                'templates_detailed' => $templates->map(function($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'category' => $template->category,
                        'difficulty_level' => $template->difficulty_level,
                        'exercises_count' => $template->exercises->count(),
                        'exercises_names' => $template->exercises->pluck('name')->toArray(),
                        'is_public' => $template->is_public,
                        'actual_duration' => $template->actual_duration,
                        'actual_calories' => $template->actual_calories,
                        'created_at' => $template->created_at->toISOString()
                    ];
                })->toArray(),
                'other_users_examples' => $otherUsersTemplates->map(function($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'user_id' => $template->user_id,
                        'category' => $template->category,
                        'difficulty_level' => $template->difficulty_level,
                        'is_public' => $template->is_public
                    ];
                })->toArray(),
                'sample_api_response' => null
            ];

            // Generate sample API response if templates exist
            if ($templates->count() > 0) {
                $sampleTemplate = $templates->first();
                $debugInfo['sample_api_response'] = [
                    'success' => true,
                    'data' => [$sampleTemplate->toArray()],
                    'message' => 'Workout templates retrieved successfully'
                ];
            }

            return $this->successResponse($debugInfo, 'Debug information retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Debug failed: ' . $e->getMessage(),
                500,
                ['trace' => $e->getTraceAsString()]
            );
        }
    }

    /**
     * Reseed workout templates for current user
     */
    public function reseedUserTemplates(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            // Delete existing templates for this user
            Workout::where('user_id', $user->id)->where('is_template', true)->delete();

            // Get some exercises to work with
            $exercises = \App\Models\Exercise::take(10)->get();

            if ($exercises->count() === 0) {
                return $this->errorResponse('No exercises found. Please seed exercises first.', 400);
            }

            // Create sample templates
            $templates = [
                [
                    'name' => 'Programme Force Débutant',
                    'description' => 'Un programme de force parfait pour débuter',
                    'category' => 'strength',
                    'difficulty_level' => 'beginner',
                    'actual_duration' => 45,
                    'actual_calories' => 200,
                    'is_public' => false
                ],
                [
                    'name' => 'Cardio HIIT Intense',
                    'description' => 'Entraînement cardio haute intensité',
                    'category' => 'hiit',
                    'difficulty_level' => 'intermediate',
                    'actual_duration' => 30,
                    'actual_calories' => 300,
                    'is_public' => true
                ],
                [
                    'name' => 'Flexibilité et Mobilité',
                    'description' => 'Améliorer la flexibilité et la mobilité',
                    'category' => 'flexibility',
                    'difficulty_level' => 'beginner',
                    'actual_duration' => 25,
                    'actual_calories' => 100,
                    'is_public' => false
                ]
            ];

            $createdTemplates = [];

            foreach ($templates as $templateData) {
                $templateData['user_id'] = $user->id;
                $templateData['is_template'] = true;
                $templateData['status'] = 'planned';

                $template = Workout::create($templateData);

                // Add some exercises to each template
                $templateExercises = $exercises->random(min(3, $exercises->count()));

                foreach ($templateExercises as $index => $exercise) {
                    $template->exercises()->attach($exercise->id, [
                        'order_index' => $index,
                        'sets' => rand(2, 4),
                        'reps' => rand(8, 15),
                        'rest_time_seconds' => 60,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                $createdTemplates[] = $template->load('exercises')->toArray();
            }

            return $this->successResponse([
                'created_count' => count($createdTemplates),
                'templates' => $createdTemplates
            ], 'Templates reseeded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Reseed failed: ' . $e->getMessage(),
                500,
                ['trace' => $e->getTraceAsString()]
            );
        }
    }
}
