<?php

namespace App\Services;

use App\Models\Workout;
use App\Models\User;
use App\Models\Exercise;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkoutService
{
    protected CalorieCalculatorService $calorieCalculator;
    protected StatisticsService $statisticsService;

    public function __construct(
        CalorieCalculatorService $calorieCalculator,
        StatisticsService $statisticsService
    ) {
        $this->calorieCalculator = $calorieCalculator;
        $this->statisticsService = $statisticsService;
    }

    // ===================================================================
    // TEMPLATE (PLAN) MANAGEMENT
    // ===================================================================

    public function createWorkoutTemplate(array $data, User $user): Workout
    {
        Log::info('Creating workout template for user: ' . $user->id, $data);

        $data['user_id'] = $user->id;
        $data['is_template'] = true;
        $data['status'] = 'planned';

        DB::beginTransaction();
        try {
            $workout = Workout::create($data);

            if (!empty($data['exercises'])) {
                $this->syncExercises($workout, $data['exercises']);
            }

            DB::commit();
            Log::info('Workout template created successfully', ['workout_id' => $workout->id]);
            return $workout->load('exercises');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WorkoutService: Failed to create workout template', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw the exception after logging and rollback
        }
    }

    public function updateWorkoutTemplate(Workout $template, array $data, User $user): Workout
    {
        if (!$template->is_template) {
            throw new \InvalidArgumentException('This workout is not a template.');
        }

        Log::info('Updating workout template: ' . $template->id, $data);

        DB::beginTransaction();
        try {
            $template->update($data);

            if (isset($data['exercises'])) {
                $this->syncExercises($template, $data['exercises']);
            }

            DB::commit();
            Log::info('Workout template updated successfully', ['workout_id' => $template->id]);
            return $template->load('exercises');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WorkoutService: Failed to update workout template', [
                'user_id' => $user->id,
                'workout_id' => $template->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw the exception after logging and rollback
        }
    }

    public function cloneTemplate(Workout $originalTemplate, User $user, ?string $newName = null): Workout
    {
        $clonedData = $originalTemplate->getAttributes();
        unset($clonedData['id'], $clonedData['created_at'], $clonedData['updated_at']);

        $clonedData['user_id'] = $user->id;
        $clonedData['name'] = $newName ?? ($originalTemplate->name . ' (Copy)');
        $clonedData['is_public'] = false;

        $newTemplate = Workout::create($clonedData);

        $exercises = $originalTemplate->workoutExercises->map(function ($pivot) {
            return $pivot->only([
                'exercise_id', 'order_index', 'sets', 'reps', 'duration_seconds', 
                'rest_time_seconds', 'target_weight', 'notes'
            ]);
        })->toArray();

        $this->syncExercises($newTemplate, $exercises);

        return $newTemplate;
    }

    // ===================================================================
    // SESSION (LOG) MANAGEMENT
    // ===================================================================

    public function startWorkout(User $user, ?int $templateId = null): Workout
    {
        Log::info('Starting workout session for user: ' . $user->id, ['template_id' => $templateId]);

        $this->endInProgressWorkouts($user);

        $data = [
            'user_id' => $user->id,
            'is_template' => false,
            'template_id' => $templateId,
            'status' => 'in_progress',
            'started_at' => now(),
            'name' => 'Custom Workout - ' . now()->format('M d, Y H:i'), // Default name for custom workouts
        ];

        $template = null;
        if ($templateId) {
            $template = Workout::find($templateId);
            if ($template) {
                $data['name'] = $template->name;
                $data['description'] = $template->description;
                $data['category'] = $template->category ?? $template->type;
                $data['difficulty'] = $template->difficulty ?? $template->difficulty_level;
                $data['type'] = $template->type ?? $template->category;
                $data['difficulty_level'] = $template->difficulty_level ?? $template->difficulty;
            }
        }

        $session = Workout::create($data);

        if ($template) {
            $exercises = $template->workoutExercises->map(function ($pivot) {
                return $pivot->only([
                    'exercise_id', 'order_index', 'sets', 'reps', 'duration_seconds',
                    'rest_time_seconds', 'target_weight', 'notes'
                ]);
            })->toArray();
            $this->syncExercises($session, $exercises);
        }

        // Clear user statistics cache to reflect new workout session
        $this->statisticsService->clearUserCache($user);

        Log::info('Workout session started', ['session_id' => $session->id]);
        return $session;
    }

    public function completeWorkout(Workout $session, array $completionData): Workout
    {
        Log::info('Completing workout session: ' . $session->id, $completionData);

        try {
            // Ensure relationships are loaded safely
            if (!$session->relationLoaded('user')) {
                $session->load('user');
            }

            $updateData = [
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(), // Explicitly set updated_at
                'notes' => $completionData['notes'] ?? $session->notes,
            ];

            if ($session->status === 'in_progress' && $session->started_at) {
                $updateData['actual_duration'] = $session->started_at->diffInMinutes(now());
            }
            $updateData['actual_duration'] = $completionData['actual_duration'] ?? $updateData['actual_duration'] ?? 0;

            // Safely calculate calories with fallback
            try {
                // Get workout type safely with multiple fallbacks
                $workoutType = 'general'; // Default fallback

                // Try to load template if not loaded and template_id exists
                if (!$session->relationLoaded('template') && $session->template_id) {
                    try {
                        $session->load('template');
                    } catch (\Exception $e) {
                        Log::warning('Failed to load template relation', ['session_id' => $session->id, 'template_id' => $session->template_id]);
                    }
                }

                // Try to get type from template first
                if ($session->relationLoaded('template') && $session->template && isset($session->template->type)) {
                    $workoutType = $session->template->type;
                }
                // Fallback to session type
                elseif (isset($session->type) && !empty($session->type)) {
                    $workoutType = $session->type;
                }
                // Fallback to category if type not available
                elseif (isset($session->category) && !empty($session->category)) {
                    $workoutType = $session->category;
                }
                // Try template fields
                elseif ($session->relationLoaded('template') && $session->template) {
                    $workoutType = $session->template->category ?? $session->template->type ?? 'general';
                }

                $updateData['actual_calories'] = $this->calorieCalculator->calculate(
                    $updateData['actual_duration'],
                    $session->user->weight ?? 70,
                    $workoutType
                );
            } catch (\Exception $e) {
                Log::warning('Failed to calculate calories, using fallback', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                    'session_type' => $session->type ?? 'null',
                    'template_loaded' => $session->relationLoaded('template'),
                    'template_exists' => $session->template ? 'yes' : 'no'
                ]);
                // Fallback calorie calculation: ~5 calories per minute
                $updateData['actual_calories'] = $updateData['actual_duration'] * 5;
            }

            $session->update($updateData);

            if (!empty($completionData['exercises'])) {
                try {
                    $this->syncExercises($session, $completionData['exercises'], true);
                } catch (\Exception $e) {
                    Log::warning('Failed to sync exercises during completion', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue without exercises sync if it fails
                }
            }

            // Update user statistics and clear cache after workout completion
            try {
                $this->statisticsService->clearUserCache($session->user);
                $this->updateUserStats($session->user, $updateData);
            } catch (\Exception $e) {
                Log::warning('Failed to update user stats, but workout completion succeeded', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage()
                ]);
                // Continue without stats update if it fails
            }

            Log::info('Workout session completed successfully', [
                'session_id' => $session->id,
                'duration' => $updateData['actual_duration'],
                'calories' => $updateData['actual_calories']
            ]);

            return $session;

        } catch (\Exception $e) {
            Log::error('Failed to complete workout session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function logWorkout(array $data, User $user): Workout
    {
        Log::info('Logging workout for user: ' . $user->id, $data);

        $data['user_id'] = $user->id;
        $data['is_template'] = false;
        $data['status'] = 'completed';
        $data['completed_at'] = $data['completed_at'] ?? now();

        if (!isset($data['actual_calories'])) {
            $data['actual_calories'] = $this->calorieCalculator->calculate(
                $data['actual_duration'], 
                $user->weight ?? 70, 
                'general'
            );
        }

        $session = Workout::create($data);

        if (!empty($data['exercises'])) {
            $this->syncExercises($session, $data['exercises'], true);
        }

        // Update user statistics and clear cache after workout logging
        $this->updateUserStats($user, $data);

        Log::info('Workout logged successfully', ['session_id' => $session->id]);
        return $session;
    }

    public function cancelWorkout(Workout $session): Workout
    {
        $session->update(['status' => 'cancelled', 'completed_at' => now()]);
        Log::info('Workout session cancelled', ['session_id' => $session->id]);
        return $session;
    }

    private function endInProgressWorkouts(User $user): void
    {
        $inProgressWorkouts = $user->workouts()->where('status', 'in_progress')->get();
        
        $inProgressWorkouts->each(function ($workout) {
            $this->cancelWorkout($workout);
        });
    }
    
    private function updateUserStats(User $user, array $workoutData): void
    {
        try {
            // This could be expanded to update user profile stats
            // For now, we rely on the StatisticsService to calculate stats dynamically
            Log::info('User stats updated after workout completion', [
                'user_id' => $user->id,
                'workout_duration' => $workoutData['actual_duration'],
                'calories_burned' => $workoutData['actual_calories']
            ]);
            
            // Clear statistics cache to force fresh calculation
            $this->statisticsService->clearUserCache($user);
            
        } catch (\Exception $e) {
            Log::error('Failed to update user stats after workout', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===================================================================
    // HELPER & UTILITY METHODS
    // ===================================================================

    private function syncExercises(Workout $workout, array $exercises, bool $isSession = false): void
    {
        $syncData = [];
        foreach ($exercises as $index => $exerciseData) {
            $exerciseId = $exerciseData['exercise_id'] ?? $exerciseData['id'] ?? null;

            if (!$exerciseId) {
                Log::warning('Exercise ID missing in syncExercises', ['exercise_data' => $exerciseData]);
                continue;
            }

            $pivotData = [
                'order_index' => $exerciseData['order_index'] ?? $index,
                'sets' => $exerciseData['sets'] ?? null,
                'reps' => $exerciseData['reps'] ?? null,
                'duration_seconds' => $exerciseData['duration_seconds'] ?? null,
                'rest_time_seconds' => $exerciseData['rest_time_seconds'] ?? 60,
                'target_weight' => $exerciseData['target_weight'] ?? null,
                'notes' => $exerciseData['notes'] ?? null,
            ];

            if ($isSession) {
                $pivotData = array_merge($pivotData, [
                    'completed_sets' => $exerciseData['completed_sets'] ?? null,
                    'completed_reps' => $exerciseData['completed_reps'] ?? null,
                    'actual_duration_seconds' => $exerciseData['actual_duration_seconds'] ?? null,
                    'weight_used' => $exerciseData['weight_used'] ?? null,
                    'is_completed' => $exerciseData['is_completed'] ?? false,
                ]);
            }

            $syncData[$exerciseId] = $pivotData;
        }

        if (!empty($syncData)) {
            $workout->exercises()->sync($syncData);
        }
    }
}
