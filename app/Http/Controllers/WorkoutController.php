<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workout;
use App\Services\WorkoutService;
use App\Services\StatisticsService;
use App\Services\CacheService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class WorkoutController extends BaseController
{
    protected WorkoutService $workoutService;
    protected StatisticsService $statisticsService;
    protected CacheService $cacheService;

    public function __construct(
        WorkoutService $workoutService,
        StatisticsService $statisticsService,
        CacheService $cacheService
    ) {
        $this->workoutService = $workoutService;
        $this->statisticsService = $statisticsService;
        $this->cacheService = $cacheService;
    }

    // =============================================
    // WORKOUT TEMPLATES MANAGEMENT - ENHANCED
    // =============================================

    public function getTemplates(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            $filters = $request->only(['category', 'difficulty', 'search']);

            Log::info('Getting workout templates for user: ' . $user->id, [
                'filters' => $filters
            ]);

            $templates = $this->cacheService->getWorkoutTemplates($user->id, $filters, function() use ($user, $request) {
                try {
                    $query = Workout::where(function($q) {
                                      // Include templates (true) and seeded workouts (null)
                                      $q->where('is_template', true)->orWhereNull('is_template');
                                  })
                                  ->where(function($q) use ($user) {
                                      // Show user's own templates OR public templates (user_id = 1 acts as system templates)
                                      $q->where('user_id', $user->id)
                                        ->orWhere('user_id', config('app.system_user_id')); // Include system templates
                                  })
                                  ->select([
                                      'id', 'name', 'description', 'type', 'category', 'difficulty', 'difficulty_level',
                                      'estimated_duration', 'estimated_calories', 'actual_duration', 'actual_calories',
                                      'user_id', 'is_template', 'created_at', 'updated_at'
                                  ])
                                  ->with([
                                      'user:id,name,first_name' // Only eager load user data, not exercises for list view
                                  ])
                                  ->withCount('exercises'); // Get exercise count efficiently

                    // Apply filters (using actual column names, not aliases)
                    if ($request->has('category') && $request->get('category') !== 'all') {
                        $query->where(function($q) use ($request) {
                            $q->where('type', $request->get('category'))
                              ->orWhere('category', $request->get('category'));
                        });
                    }

                    if ($request->has('difficulty') && $request->get('difficulty') !== 'all') {
                        $query->where(function($q) use ($request) {
                            $q->where('difficulty', $request->get('difficulty'))
                              ->orWhere('difficulty_level', $request->get('difficulty'));
                        });
                    }

                    if ($request->has('search') && !empty($request->get('search'))) {
                        $search = $request->get('search');
                        $query->where(function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('description', 'like', "%{$search}%");
                        });
                    }

                    $templates = $query->orderBy('created_at', 'desc')->get();

                    Log::info('Found ' . $templates->count() . ' workout templates', [
                        'template_names' => $templates->pluck('name')->toArray(),
                        'has_exercises' => $templates->map(function($t) {
                            return [
                                'name' => $t->name,
                                'exercise_count' => $t->exercises_count ?? 0
                            ];
                        })->toArray()
                    ]);

                    // Convert to array with proper exercise formatting
                    return $templates->map(function ($template) {
                        $templateArray = $template->toArray();
                        // Use the efficient exercises_count instead of loading all exercises
                        $templateArray['exercise_count'] = $template->exercises_count ?? 0;
                        return $templateArray;
                    })->toArray();

                } catch (\Exception $e) {
                    Log::error('Error getting workout templates', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Return empty array instead of failing
                    return [];
                }
            });

            return $this->successResponse($templates, 'Workout templates retrieved successfully');
        }, 'Get workout templates');
    }

    public function getCurrentTemplate()
    {
        return $this->execute(function () {
            $user = $this->getAuthenticatedUser();
            
            Log::info('Getting current template for user: ' . $user->id);
            
            try {
                $currentTemplate = Workout::where('is_template', true)
                                        ->where('user_id', $user->id)
                                        ->with(['exercises' => function($exerciseQuery) {
                                            $exerciseQuery->orderBy('workout_exercises.order_index', 'asc');
                                        }])
                                        ->orderBy('created_at', 'desc')
                                        ->first();

                if (!$currentTemplate) {
                    Log::info('No current template found, user might not have any templates yet', ['user_id' => $user->id]);
                    
                    return $this->successResponse(null, 'No current workout template found');
                }

                return $this->successResponse($currentTemplate->toArray(), 'Current workout template retrieved successfully');

            } catch (\Exception $e) {
                Log::error('Error getting current template', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->successResponse(null, 'No current workout template found');
            }

        }, 'Get current workout template');
    }

    public function showTemplate($id)
    {
        return $this->execute(function () use ($id) {
            $user = $this->getAuthenticatedUser();

            try {
                $template = Workout::where('is_template', true)
                                 ->where(function($q) use ($user) {
                                     $q->where('user_id', $user->id)
                                       ->orWhere('is_public', true);
                                 })
                                 ->with([
                                     'exercises' => function($exerciseQuery) {
                                         $exerciseQuery->orderBy('workout_exercises.order_index', 'asc');
                                     },
                                     'user:id,name,weight' // Include user for calorie calculations
                                 ])
                                 ->findOrFail($id);

                Log::info('Template retrieved successfully', [
                    'template_id' => $id,
                    'template_name' => $template->name,
                    'exercise_count' => $template->exercises->count(),
                    'estimated_duration' => $template->estimated_duration,
                    'estimated_calories' => $template->estimated_calories
                ]);

                return $this->successResponse($template->toArray(), 'Workout template retrieved successfully');

            } catch (\Exception $e) {
                Log::error('Error showing template', [
                    'template_id' => $id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                return $this->notFoundResponse('Workout template not found');
            }
        }, 'Show workout template');
    }

    // =============================================
    // Debug methods have been moved to:
    // App\Http\Controllers\Admin\WorkoutDebugController
    // =============================================

    public function createTemplate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'exercises' => 'nullable|array',
                'category' => 'required|string|in:strength,cardio,hiit,flexibility',
                'difficulty_level' => 'required|string|in:beginner,intermediate,advanced',
                'is_public' => 'nullable|boolean',
            ]);

            try {
                $template = $this->workoutService->createWorkoutTemplate($validated, $user);
                return $this->createdResponse($template->toArray(), 'Workout template created successfully');
                
            } catch (\Exception $e) {
                Log::error('Error creating template', [
                    'user_id' => $user->id,
                    'data' => $validated,
                    'error' => $e->getMessage()
                ]);
                
                return $this->errorResponse('Failed to create workout template', 500);
            }

        }, 'Create workout template');
    }

    public function updateTemplate(Request $request, $id)
    {
        return $this->execute(function () use ($request, $id) {
            $user = $this->getAuthenticatedUser();
            
            try {
                $template = Workout::where('is_template', true)
                                 ->where('user_id', $user->id)
                                 ->findOrFail($id);

                $validated = $request->validate([
                    'name' => 'sometimes|string|max:255',
                    'description' => 'nullable|string|max:1000',
                    'exercises' => 'sometimes|array',
                    'category' => 'sometimes|string|in:strength,cardio,hiit,flexibility',
                    'difficulty_level' => 'sometimes|string|in:beginner,intermediate,advanced',
                    'is_public' => 'nullable|boolean',
                ]);

                $updatedTemplate = $this->workoutService->updateWorkoutTemplate($template, $validated, $user);
                return $this->successResponse($updatedTemplate->toArray(), 'Workout template updated successfully');
                
            } catch (\Exception $e) {
                Log::error('Error updating template', [
                    'template_id' => $id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->errorResponse('Failed to update workout template', 500);
            }

        }, 'Update workout template');
    }

    public function deleteTemplate($id)
    {
        return $this->execute(function () use ($id) {
            $user = $this->getAuthenticatedUser();
            
            try {
                $template = Workout::where('is_template', true)
                                 ->where('user_id', $user->id)
                                 ->findOrFail($id);
                                 
                $template->delete();
                return $this->successResponse(null, 'Workout template deleted successfully');
                
            } catch (\Exception $e) {
                Log::error('Error deleting template', [
                    'template_id' => $id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->errorResponse('Failed to delete workout template', 500);
            }
        }, 'Delete workout template');
    }

    public function duplicateTemplate($id)
    {
        return $this->execute(function () use ($id) {
            $user = $this->getAuthenticatedUser();
            
            try {
                $originalTemplate = Workout::where('is_template', true)
                                         ->where('user_id', $user->id)
                                         ->findOrFail($id);
                                         
                $duplicatedTemplate = $this->workoutService->cloneTemplate($originalTemplate, $user);
                return $this->createdResponse($duplicatedTemplate->toArray(), 'Workout template duplicated successfully');
                
            } catch (\Exception $e) {
                Log::error('Error duplicating template', [
                    'template_id' => $id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->errorResponse('Failed to duplicate workout template', 500);
            }
        }, 'Duplicate workout template');
    }

    // =============================================
    // WORKOUT LOGS (SESSIONS) MANAGEMENT
    // =============================================

    public function getLogs(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            Log::info('Getting workout logs for user: ' . $user->id, [
                'filters' => $request->all()
            ]);
            
            try {
                $query = Workout::where('is_template', false)
                              ->where('user_id', $user->id)
                              ->with(['template', 'exercises']);
                
                // Apply filters safely
                if ($request->has('status') && !empty($request->get('status'))) {
                    $query->where('status', $request->get('status'));
                }
                
                if ($request->has('template_id') && !empty($request->get('template_id'))) {
                    $query->where('template_id', $request->get('template_id'));
                }
                
                if ($request->has('days') && is_numeric($request->get('days'))) {
                    $days = (int)$request->get('days');
                    $query->where('completed_at', '>=', now()->subDays($days));
                }

                $limit = min((int) $request->get('limit', 20), 100);
                
                $logs = $query->orderBy('completed_at', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->limit($limit)
                            ->get();

                Log::info('Found ' . $logs->count() . ' workout sessions');

                $logsArray = $logs->map(function ($log) {
                    return $log->toArray();
                })->toArray();

                return $this->successResponse($logsArray, 'Workout logs retrieved successfully');

            } catch (\Exception $e) {
                Log::error('Error getting workout logs', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Return empty array instead of failing
                return $this->successResponse([], 'No workout sessions found');
            }

        }, 'Get workout logs');
    }

    public function showLog($id)
    {
        return $this->execute(function () use ($id) {
            $user = $this->getAuthenticatedUser();
            
            try {
                $log = Workout::where('is_template', false)
                            ->where('user_id', $user->id)
                            ->with(['template', 'exercises'])
                            ->findOrFail($id);
                            
                return $this->successResponse($log->toArray(), 'Workout log retrieved successfully');
                
            } catch (\Exception $e) {
                Log::error('Error showing workout log', [
                    'log_id' => $id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->notFoundResponse('Workout session not found');
            }
        }, 'Show workout log');
    }

    public function logWorkout(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();

            $validated = $request->validate([
                'template_id' => 'nullable|exists:workouts,id',
                'name' => 'required|string|max:255',
                'actual_duration' => 'required|integer|min:1',
                'actual_calories' => 'nullable|integer|min:0',
                'notes' => 'nullable|string',
                'completed_at' => 'nullable|date',
                'exercises' => 'nullable|array',
                'category' => 'nullable|string|in:strength,cardio,hiit,flexibility',
                'difficulty_level' => 'nullable|string|in:beginner,intermediate,advanced',
            ]);

            try {
                $log = $this->workoutService->logWorkout($validated, $user);
                return $this->createdResponse($log->toArray(), 'Workout logged successfully');
                
            } catch (\Exception $e) {
                Log::error('Error logging workout', [
                    'user_id' => $user->id,
                    'data' => $validated,
                    'error' => $e->getMessage()
                ]);
                
                return $this->errorResponse('Failed to log workout', 500);
            }

        }, 'Log workout');
    }

    public function startWorkout(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();

            $validated = $request->validate([
                'template_id' => 'nullable|exists:workouts,id',
            ]);

            try {
                $session = $this->workoutService->startWorkout($user, $validated['template_id'] ?? null);
                return $this->createdResponse($session->toArray(), 'Workout session started successfully');
                
            } catch (\Exception $e) {
                Log::error('Error starting workout', [
                    'user_id' => $user->id,
                    'template_id' => $validated['template_id'] ?? null,
                    'error' => $e->getMessage()
                ]);
                
                return $this->errorResponse('Failed to start workout session', 500);
            }

        }, 'Start workout session');
    }

    public function completeWorkout(Request $request, $id)
    {
        return $this->execute(function () use ($request, $id) {
            $user = $this->getAuthenticatedUser();

            try {
                Log::info('Starting workout completion', [
                    'session_id' => $id,
                    'user_id' => $user->id,
                    'request_data' => $request->all()
                ]);

                // First check if the workout exists with detailed logging
                $session = Workout::where('is_template', false)
                                ->where('user_id', $user->id)
                                ->find($id);

                if (!$session) {
                    // Get available sessions for debugging
                    $availableSessions = Workout::where('is_template', false)
                        ->where('user_id', $user->id)
                        ->select('id', 'name', 'status', 'started_at', 'completed_at')
                        ->get();

                    Log::warning('Workout session not found', [
                        'requested_session_id' => $id,
                        'user_id' => $user->id,
                        'available_sessions' => $availableSessions->toArray(),
                        'total_available' => $availableSessions->count()
                    ]);

                    return $this->errorResponse(
                        "Workout session #{$id} not found. Please check if the session exists or start a new workout.",
                        404,
                        [
                            'session_id' => $id,
                            'available_sessions' => $availableSessions->map(function($session) {
                                return [
                                    'id' => $session->id,
                                    'name' => $session->name,
                                    'status' => $session->status,
                                    'started_at' => $session->started_at,
                                    'can_complete' => $session->status === 'in_progress'
                                ];
                            })
                        ]
                    );
                }

                // Load relationships after confirming session exists
                $session->load(['user', 'template']);

                Log::info('Session found for completion', [
                    'session_id' => $session->id,
                    'session_name' => $session->name,
                    'session_status' => $session->status,
                    'has_template' => $session->template ? 'yes' : 'no',
                    'template_id' => $session->template_id ?? 'null',
                    'started_at' => $session->started_at,
                    'completed_at' => $session->completed_at
                ]);

                // Validate session status
                if ($session->status === 'completed') {
                    Log::warning('Attempting to complete already completed session', [
                        'session_id' => $session->id,
                        'completed_at' => $session->completed_at
                    ]);

                    return $this->errorResponse(
                        'This workout session has already been completed.',
                        400,
                        [
                            'session_id' => $session->id,
                            'status' => $session->status,
                            'completed_at' => $session->completed_at
                        ]
                    );
                }

                // Validate request data
                $validated = $request->validate([
                    'notes' => 'nullable|string|max:1000',
                    'actual_duration' => 'sometimes|integer|min:1|max:600', // Max 10 hours
                    'actual_calories' => 'sometimes|integer|min:1|max:5000',
                    'exercises' => 'nullable|array',
                ]);

                Log::info('Validated request data', [
                    'session_id' => $session->id,
                    'validated_data' => $validated
                ]);

                // Complete the workout using the service
                $completedWorkout = $this->workoutService->completeWorkout($session, $validated);

                Log::info('Workout completion successful', [
                    'session_id' => $completedWorkout->id,
                    'status' => $completedWorkout->status,
                    'completed_at' => $completedWorkout->completed_at,
                    'actual_duration' => $completedWorkout->actual_duration,
                    'actual_calories' => $completedWorkout->actual_calories
                ]);

                return $this->successResponse($completedWorkout->fresh()->toArray(), 'Workout session completed successfully');

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                Log::warning('Workout session not found via findOrFail', [
                    'session_id' => $id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                return $this->errorResponse(
                    "Workout session #{$id} not found.",
                    404,
                    ['session_id' => $id]
                );

            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::warning('Validation failed for workout completion', [
                    'session_id' => $id,
                    'user_id' => $user->id,
                    'validation_errors' => $e->errors()
                ]);

                return $this->errorResponse(
                    'Validation failed: ' . collect($e->errors())->flatten()->implode(', '),
                    422,
                    ['validation_errors' => $e->errors()]
                );

            } catch (\Exception $e) {
                Log::error('Error completing workout', [
                    'session_id' => $id,
                    'user_id' => $user->id,
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);

                return $this->errorResponse(
                    'Failed to complete workout session: ' . $e->getMessage(),
                    500,
                    [
                        'session_id' => $id,
                        'error_details' => $e->getMessage()
                    ]
                );
            }

        }, 'Complete workout session');
    }

    public function deleteLog($id)
    {
        return $this->execute(function () use ($id) {
            $user = $this->getAuthenticatedUser();
            
            try {
                $log = Workout::where('is_template', false)
                            ->where('user_id', $user->id)
                            ->findOrFail($id);
                            
                $log->delete();
                return $this->successResponse(null, 'Workout log deleted successfully');
                
            } catch (\Exception $e) {
                Log::error('Error deleting workout log', [
                    'log_id' => $id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->errorResponse('Failed to delete workout session', 500);
            }
        }, 'Delete workout log');
    }

    // =============================================
    // STATISTICS AND ANALYTICS
    // =============================================

    public function getStats()
    {
        return $this->execute(function () {
            $user = $this->getAuthenticatedUser();
            
            Log::info('Getting workout stats for user: ' . $user->id);
            
            try {
                $stats = $this->statisticsService->getUserStats($user);
                
                Log::info('Workout stats retrieved successfully', [
                    'user_id' => $user->id,
                    'stats' => $stats
                ]);
                
                return $this->successResponse($stats, 'Workout statistics retrieved successfully');
                
            } catch (\Exception $e) {
                Log::error('Error getting workout stats', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Return default stats on error
                $defaultStats = [
                    'total_workouts' => 0,
                    'total_minutes' => 0,
                    'total_calories' => 0,
                    'current_streak' => 0,
                    'weekly_workouts' => 0,
                    'monthly_workouts' => 0,
                    'average_duration' => 0,
                    'favorite_category' => 'strength'
                ];
                
                return $this->successResponse($defaultStats, 'Default workout statistics provided');
            }
        }, 'Get workout statistics');
    }

    public function getWeeklyStats()
    {
        return $this->execute(function () {
            $user = $this->getAuthenticatedUser();
            
            try {
                $weeklyData = $this->statisticsService->getWeeklyData($user);
                return $this->successResponse($weeklyData, 'Weekly statistics retrieved successfully');
                
            } catch (\Exception $e) {
                Log::error('Error getting weekly stats', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->successResponse([], 'No weekly data available');
            }
        }, 'Get weekly statistics');
    }

    public function getMonthlyStats()
    {
        return $this->execute(function () {
            $user = $this->getAuthenticatedUser();
            
            try {
                $monthlyData = $this->statisticsService->getMonthlyOverview($user);
                return $this->successResponse($monthlyData, 'Monthly statistics retrieved successfully');
                
            } catch (\Exception $e) {
                Log::error('Error getting monthly stats', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->successResponse([], 'No monthly data available');
            }
        }, 'Get monthly statistics');
    }

    public function getConsistency(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            try {
                $days = (int) $request->get('days', 30);
                $consistency = $this->statisticsService->getConsistencyScore($user, $days);
                
                return $this->successResponse([
                    'consistency_score' => $consistency,
                    'period_days' => $days
                ], 'Consistency data retrieved successfully');
                
            } catch (\Exception $e) {
                Log::error('Error getting consistency data', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->successResponse([
                    'consistency_score' => 0,
                    'period_days' => 30
                ], 'Default consistency data provided');
            }
        }, 'Get consistency data');
    }

    // =============================================
    // ADDITIONAL HELPER METHODS
    // =============================================

    public function getWorkoutHistory(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            try {
                $query = Workout::where('is_template', false)
                              ->where('user_id', $user->id)
                              ->where('status', 'completed')
                              ->with(['template', 'exercises']);

                // Apply date range filter
                if ($request->has('start_date') && $request->has('end_date')) {
                    $query->whereBetween('completed_at', [
                        $request->get('start_date'),
                        $request->get('end_date')
                    ]);
                }

                // Apply category filter
                if ($request->has('category') && $request->get('category') !== 'all') {
                    $query->where('category', $request->get('category'));
                }

                $limit = min((int) $request->get('limit', 50), 100);
                
                $history = $query->orderBy('completed_at', 'desc')
                               ->limit($limit)
                               ->get();

                $historyArray = $history->map(function ($session) {
                    return $session->toArray();
                })->toArray();

                return $this->successResponse($historyArray, 'Workout history retrieved successfully');
                
            } catch (\Exception $e) {
                Log::error('Error getting workout history', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return $this->successResponse([], 'No workout history found');
            }
        }, 'Get workout history');
    }

    public function getPublicTemplates(Request $request)
    {
        return $this->execute(function () use ($request) {
            try {
                $query = Workout::where('is_template', true)
                              ->where('is_public', true)
                              ->with(['user:id,name', 'exercises']);
                
                // Apply filters
                if ($request->has('category') && $request->get('category') !== 'all') {
                    $query->where('category', $request->get('category'));
                }
                
                if ($request->has('difficulty') && $request->get('difficulty') !== 'all') {
                    $query->where('difficulty_level', $request->get('difficulty'));
                }

                if ($request->has('search') && !empty($request->get('search'))) {
                    $search = $request->get('search');
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                    });
                }

                $limit = min((int) $request->get('limit', 20), 50);
                
                $templates = $query->orderBy('created_at', 'desc')
                                 ->limit($limit)
                                 ->get();

                $templatesArray = $templates->map(function ($template) {
                    return $template->toArray();
                })->toArray();

                return $this->successResponse($templatesArray, 'Public workout templates retrieved successfully');

            } catch (\Exception $e) {
                Log::error('Error getting public templates', [
                    'error' => $e->getMessage()
                ]);
                
                return $this->successResponse([], 'No public templates found');
            }

        }, 'Get public workout templates');
    }

    public function searchWorkouts(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            $validated = $request->validate([
                'query' => 'required|string|min:2|max:100',
                'type' => 'nullable|in:templates,sessions,all',
                'limit' => 'nullable|integer|min:1|max:50'
            ]);

            try {
                $query = $validated['query'];
                $type = $validated['type'] ?? 'all';
                $limit = $validated['limit'] ?? 20;

                $results = [];

                if ($type === 'templates' || $type === 'all') {
                    $templates = Workout::where('is_template', true)
                                      ->where('user_id', $user->id)
                                      ->with('exercises')
                                      ->where(function($q) use ($query) {
                                          $q->where('name', 'like', "%{$query}%")
                                            ->orWhere('description', 'like', "%{$query}%");
                                      })
                                      ->limit($limit)
                                      ->get();
                    
                    $results['templates'] = $templates->map(function($template) {
                        return $template->toArray();
                    })->toArray();
                }

                if ($type === 'sessions' || $type === 'all') {
                    $sessions = Workout::where('is_template', false)
                                     ->where('user_id', $user->id)
                                     ->with(['template', 'exercises'])
                                     ->where(function($q) use ($query) {
                                         $q->where('name', 'like', "%{$query}%")
                                           ->orWhere('notes', 'like', "%{$query}%");
                                     })
                                     ->limit($limit)
                                     ->get();
                    
                    $results['sessions'] = $sessions->map(function($session) {
                        return $session->toArray();
                    })->toArray();
                }

                return $this->successResponse($results, 'Search results retrieved successfully');

            } catch (\Exception $e) {
                Log::error('Error searching workouts', [
                    'user_id' => $user->id,
                    'query' => $validated['query'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                
                return $this->successResponse(['templates' => [], 'sessions' => []], 'No search results found');
            }
        }, 'Search workouts');
    }}