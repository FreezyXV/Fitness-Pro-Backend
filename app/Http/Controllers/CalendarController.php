<?php
// app/Http/Controllers/CalendarController.php - FIXED VERSION
namespace App\Http\Controllers;

use App\Models\CalendarTask;
use App\Models\WorkoutPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CalendarController extends BaseController
{
    /**
     * Display a listing of calendar tasks (optimized)
     */
    public function index(Request $request)
    {
        return $this->execute(function () use ($request) {
            $query = CalendarTask::with(['workoutPlan:id,name'])
                               ->forUser($this->getUserId())
                               ->orderBy('task_date', 'asc')
                               ->orderBy('reminder_time', 'asc');

            // Apply filters efficiently
            $this->applyFilters($query, $request, [
                'task_type' => 'task_type',
                'is_completed' => 'is_completed',
                'priority' => 'priority'
            ]);

            // Date range filters
            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('task_date', [
                    $request->date_from,
                    $request->date_to
                ]);
            }

            if ($request->has('month') && $request->has('year')) {
                $query->whereMonth('task_date', $request->month)
                      ->whereYear('task_date', $request->year);
            }

            // Use pagination for better performance
            $tasks = $this->paginate($query, $request, 50, 100);

            // Add computed fields efficiently
            $tasks->getCollection()->transform(function ($task) {
                return $this->enhanceTaskData($task);
            });

            return $this->paginatedResponse($tasks, 'Calendar tasks retrieved successfully');

        }, 'Get calendar tasks');
    }

    /**
     * Store a newly created calendar task (optimized)
     */
    public function store(Request $request)
    {
        return $this->execute(function () use ($request) {
            $validated = $this->validateTaskData($request);
            $validated['user_id'] = $this->getUserId();
            $validated['is_completed'] = false;

            // Handle recurring tasks
            if ($validated['recurring'] ?? false) {
                $tasks = $this->createRecurringTasks($validated);
                return $this->successResponse($tasks, 'Recurring calendar tasks created successfully', 201);
            }

            // Create single task
            $task = CalendarTask::create($validated);
            $task->load(['workoutPlan:id,name']);
            
            return $this->createdResponse(
                $this->enhanceTaskData($task), 
                'Calendar task created successfully'
            );

        }, 'Create calendar task');
    }

    /**
     * Display the specified calendar task (optimized)
     */
    public function show($id)
    {
        return $this->execute(function () use ($id) {
            $task = CalendarTask::with(['workoutPlan:id,name'])
                              ->forUser($this->getUserId())
                              ->findOrFail($id);

            return $this->successResponse(
                $this->enhanceTaskData($task), 
                'Calendar task retrieved successfully'
            );

        }, 'Get calendar task');
    }

    /**
     * Update the specified calendar task (optimized)
     */
    public function update(Request $request, $id)
    {
        return $this->execute(function () use ($request, $id) {
            $task = CalendarTask::forUser($this->getUserId())->findOrFail($id);
            $validated = $this->validateTaskData($request, false);

            $task->update($validated);
            $task->load(['workoutPlan:id,name']);
            
            return $this->updatedResponse(
                $this->enhanceTaskData($task), 
                'Calendar task updated successfully'
            );

        }, 'Update calendar task');
    }

    /**
     * Remove the specified calendar task (optimized)
     */
    public function destroy($id)
    {
        return $this->execute(function () use ($id) {
            $task = CalendarTask::forUser($this->getUserId())->findOrFail($id);
            $task->delete();

            // Clear related cache
            $this->clearUserCache("calendar_*");

            return $this->deletedResponse('Calendar task deleted successfully');

        }, 'Delete calendar task');
    }

    /**
     * Get tasks for a specific month (FIXED - supports both formats)
     */
    public function getMonthTasks(Request $request, $monthKey)
    {
        return $this->execute(function () use ($monthKey) {
            // Parse and validate month key - support both YYYY-MM and month/YYYY-MM formats
            $parts = explode('-', str_replace('month/', '', $monthKey));
            if (count($parts) !== 2) {
                return $this->errorResponse('Invalid month format. Use YYYY-MM', 400);
            }

            [$year, $month] = $parts;
            $year = (int) $year;
            $month = (int) $month;

            if ($year < 2020 || $year > 2030 || $month < 1 || $month > 12) {
                return $this->errorResponse('Invalid year or month', 400);
            }

            // Use caching for month data
            $cacheKey = "calendar_month_{$this->getUserId()}_{$year}_{$month}";
            
            $data = $this->cacheResponse($cacheKey, function () use ($year, $month) {
                $tasks = CalendarTask::with(['workoutPlan:id,name'])
                                   ->forUser($this->getUserId())
                                   ->whereYear('task_date', $year)
                                   ->whereMonth('task_date', $month)
                                   ->orderBy('task_date', 'asc')
                                   ->orderBy('reminder_time', 'asc')
                                   ->get();

                // Enhance tasks and calculate stats
                $enhancedTasks = $tasks->map(fn($task) => $this->enhanceTaskData($task));
                $stats = $this->calculateMonthStats($tasks);

                return [
                    'tasks' => $enhancedTasks,
                    'stats' => $stats,
                    'month' => $month,
                    'year' => $year
                ];
            }, 300); // Cache for 5 minutes

            return $this->successResponse($data, 'Monthly tasks retrieved successfully');

        }, 'Get monthly tasks');
    }

    /**
     * Get today's tasks (optimized)
     */
    public function getTodayTasks()
    {
        return $this->execute(function () {
            $cacheKey = "calendar_today_{$this->getUserId()}_" . Carbon::today()->format('Y-m-d');
            
            $data = $this->cacheResponse($cacheKey, function () {
                $tasks = CalendarTask::with(['workoutPlan:id,name'])
                                   ->forUser($this->getUserId())
                                   ->whereDate('task_date', Carbon::today())
                                   ->orderBy('is_completed', 'asc')
                                   ->orderBy('reminder_time', 'asc')
                                   ->get();

                $enhancedTasks = $tasks->map(fn($task) => $this->enhanceTaskData($task));
                $stats = $this->calculateDayStats($tasks);

                return [
                    'tasks' => $enhancedTasks,
                    'stats' => $stats
                ];
            }, 60); // Cache for 1 minute

            return $this->successResponse($data, 'Today\'s tasks retrieved successfully');

        }, 'Get today tasks');
    }

    /**
     * Get this week's tasks (optimized)
     */
    public function getWeekTasks()
    {
        return $this->execute(function () {
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
            
            $cacheKey = "calendar_week_{$this->getUserId()}_{$startOfWeek->format('Y-m-d')}";
            
            $data = $this->cacheResponse($cacheKey, function () use ($startOfWeek, $endOfWeek) {
                $tasks = CalendarTask::with(['workoutPlan:id,name'])
                                   ->forUser($this->getUserId())
                                   ->whereBetween('task_date', [$startOfWeek, $endOfWeek])
                                   ->orderBy('task_date', 'asc')
                                   ->orderBy('reminder_time', 'asc')
                                   ->get();

                $enhancedTasks = $tasks->map(fn($task) => $this->enhanceTaskData($task));
                $tasksByDay = $enhancedTasks->groupBy(fn($task) => $task['task_date']);
                $stats = $this->calculateWeekStats($tasks);

                return [
                    'tasks' => $enhancedTasks,
                    'tasks_by_day' => $tasksByDay,
                    'stats' => $stats,
                    'week_start' => $startOfWeek->format('Y-m-d'),
                    'week_end' => $endOfWeek->format('Y-m-d')
                ];
            }, 300); // Cache for 5 minutes

            return $this->successResponse($data, 'Week tasks retrieved successfully');

        }, 'Get week tasks');
    }

    /**
     * Mark task as completed (optimized)
     */
    public function markComplete($id)
    {
        return $this->execute(function () use ($id) {
            $task = CalendarTask::forUser($this->getUserId())->findOrFail($id);
            $task->update(['is_completed' => true]);
            
            $task->load(['workoutPlan:id,name']);
            
            // Clear related cache
            $this->clearUserCache("calendar_*");

            return $this->successResponse(
                $this->enhanceTaskData($task), 
                'Task marked as completed successfully'
            );

        }, 'Mark task complete');
    }

    /**
     * Mark task as incomplete (optimized)
     */
    public function markIncomplete($id)
    {
        return $this->execute(function () use ($id) {
            $task = CalendarTask::forUser($this->getUserId())->findOrFail($id);
            $task->update(['is_completed' => false]);
            
            $task->load(['workoutPlan:id,name']);
            
            // Clear related cache
            $this->clearUserCache("calendar_*");

            return $this->successResponse(
                $this->enhanceTaskData($task), 
                'Task marked as incomplete successfully'
            );

        }, 'Mark task incomplete');
    }

    /**
     * Get calendar statistics (optimized)
     */
    public function getStats(Request $request)
    {
        return $this->execute(function () use ($request) {
            $period = $request->get('period', 'month');
            
            $cacheKey = "calendar_stats_{$this->getUserId()}_{$period}";
            
            $stats = $this->cacheResponse($cacheKey, function () use ($period) {
                $query = CalendarTask::forUser($this->getUserId());

                // Apply period filter efficiently
                switch ($period) {
                    case 'week':
                        $query->whereBetween('task_date', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                        ]);
                        break;
                    case 'month':
                        $query->whereMonth('task_date', Carbon::now()->month)
                              ->whereYear('task_date', Carbon::now()->year);
                        break;
                    case 'year':
                        $query->whereYear('task_date', Carbon::now()->year);
                        break;
                }

                // Get statistics in single query
                $baseStats = $query->selectRaw('
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN is_completed = 0 THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN is_completed = 0 AND task_date < CURDATE() THEN 1 ELSE 0 END) as overdue_tasks
                ')->first();

                // Get tasks by type
                $tasksByType = $query->selectRaw('task_type, COUNT(*) as count')
                                   ->groupBy('task_type')
                                   ->pluck('count', 'task_type')
                                   ->toArray();

                $completionRate = $baseStats->total_tasks > 0 
                    ? round(($baseStats->completed_tasks / $baseStats->total_tasks) * 100, 1)
                    : 0;

                return [
                    'total_tasks' => $baseStats->total_tasks,
                    'completed_tasks' => $baseStats->completed_tasks,
                    'pending_tasks' => $baseStats->pending_tasks,
                    'overdue_tasks' => $baseStats->overdue_tasks,
                    'completion_rate' => $completionRate,
                    'tasks_by_type' => [
                        'workout' => $tasksByType['workout'] ?? 0,
                        'goal' => $tasksByType['goal'] ?? 0,
                        'rest' => $tasksByType['rest'] ?? 0,
                        'nutrition' => $tasksByType['nutrition'] ?? 0,
                        'reminder' => $tasksByType['reminder'] ?? 0,
                    ],
                    'period' => $period
                ];
            }, 600); // Cache for 10 minutes

            return $this->successResponse($stats, 'Calendar statistics retrieved successfully');

        }, 'Get calendar stats');
    }

    /**
     * Bulk update tasks (optimized)
     */
    public function bulkUpdate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $validated = $request->validate([
                'task_ids' => 'required|array|max:100', // Limit bulk operations
                'task_ids.*' => 'integer|exists:calendar_tasks,id',
                'action' => 'required|in:complete,incomplete,delete,update_priority',
                'priority' => 'required_if:action,update_priority|in:low,medium,high'
            ]);

            $taskIds = $validated['task_ids'];
            $action = $validated['action'];

            // Ensure user owns all tasks
            $userTaskCount = CalendarTask::forUser($this->getUserId())
                                       ->whereIn('id', $taskIds)
                                       ->count();

            if ($userTaskCount !== count($taskIds)) {
                return $this->errorResponse('Some tasks not found or not owned by user', 404);
            }

            $result = $this->processBulkOperation($taskIds, function ($taskId) use ($action, $validated) {
                $query = CalendarTask::forUser($this->getUserId())->where('id', $taskId);
                
                switch ($action) {
                    case 'complete':
                        return $query->update(['is_completed' => true]);
                    case 'incomplete':
                        return $query->update(['is_completed' => false]);
                    case 'delete':
                        return $query->delete();
                    case 'update_priority':
                        return $query->update(['priority' => $validated['priority']]);
                }
                return false;
            });

            // Clear related cache
            $this->clearUserCache("calendar_*");

            return $this->successResponse([
                'updated_count' => $result['success'],
                'failed_count' => $result['failed'],
                'action' => $action
            ], "Bulk {$action} operation completed successfully");

        }, 'Bulk update tasks');
    }

    /**
     * Get workout context data for a specific date.
     */
    public function getWorkoutContext(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            $date = Carbon::parse($request->get('date', Carbon::today()->toDateString()));

            $workoutTasks = CalendarTask::forUser($user->id)
                ->whereDate('task_date', $date)
                ->where('task_type', 'workout')
                ->get();

            $hasWorkoutToday = $workoutTasks->isNotEmpty();
            $workoutType = null;
            $workoutDuration = 0;
            $estimatedCaloriesBurned = 0;
            $isPreWorkout = false;
            $isPostWorkout = false;

            if ($hasWorkoutToday) {
                // For simplicity, take the first workout task for context, or aggregate
                $firstWorkout = $workoutTasks->first();
                $workoutType = $firstWorkout->workoutPlan->category ?? $firstWorkout->task_type;
                $workoutDuration = $workoutTasks->sum('duration');
                // This would require a more complex calculation based on actual workouts logged
                // For now, we'll use a placeholder or derive from task duration
                $estimatedCaloriesBurned = $workoutDuration * 5; // Example: 5 calories per minute

                // Determine pre/post workout status based on current time and task times
                $now = Carbon::now();
                if ($firstWorkout->reminder_time) {
                    $taskTime = Carbon::parse($firstWorkout->task_date . ' ' . $firstWorkout->reminder_time);
                    if ($now->lessThan($taskTime)) {
                        $isPreWorkout = true;
                    } elseif ($now->greaterThan($taskTime) && $now->diffInHours($taskTime) < 3) { // within 3 hours after
                        $isPostWorkout = true;
                    }
                }
            }

            $context = [
                'hasWorkoutToday' => $hasWorkoutToday,
                'workoutType' => $workoutType,
                'workoutDuration' => $workoutDuration,
                'estimatedCaloriesBurned' => $estimatedCaloriesBurned,
                'isPreWorkout' => $isPreWorkout,
                'isPostWorkout' => $isPostWorkout,
            ];

            return $this->successResponse($context, 'Workout context retrieved successfully');
        }, 'Get Workout Context');
    }

    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================

    /**
     * Validate task data
     */
    private function validateTaskData(Request $request, bool $isCreate = true): array
    {
        $rules = [
            'title' => ($isCreate ? 'required' : 'sometimes') . '|string|max:255',
            'description' => 'nullable|string|max:1000',
            'task_date' => ($isCreate ? 'required' : 'sometimes') . '|date',
            'task_type' => ($isCreate ? 'required' : 'sometimes') . '|in:workout,goal,reminder,nutrition,rest,other',
            'workout_plan_id' => 'nullable|exists:workout_plans,id',
            'reminder_time' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high',
            'duration' => 'nullable|integer|min:5|max:480',
            'recurring' => 'nullable|boolean',
            'recurring_type' => 'nullable|in:daily,weekly,biweekly,monthly',
            'recurring_end_date' => 'nullable|date|after:task_date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ];

        if (!$isCreate) {
            $rules['is_completed'] = 'sometimes|boolean';
        }

        return $request->validate($rules);
    }

    /**
     * Enhance task data with computed fields
     */
    private function enhanceTaskData($task): array
    {
        $taskArray = $task->toArray();
        
        $taskArray['is_today'] = $task->task_date->isToday();
        $taskArray['is_overdue'] = !$task->is_completed && $task->task_date->isPast();
        $taskArray['is_future'] = $task->task_date->isFuture();
        $taskArray['days_until'] = $task->task_date->diffInDays(Carbon::now(), false);
        
        return $taskArray;
    }

    /**
     * Create recurring tasks efficiently
     */
    private function createRecurringTasks(array $validated): array
    {
        $tasks = [];
        $startDate = Carbon::parse($validated['task_date']);
        $endDate = Carbon::parse($validated['recurring_end_date']);
        $recurringType = $validated['recurring_type'];

        $currentDate = $startDate->copy();
        $taskData = array_except($validated, ['recurring', 'recurring_type', 'recurring_end_date']);

        $batchData = [];
        while ($currentDate->lte($endDate) && count($batchData) < 365) { // Limit to prevent abuse
            $taskData['task_date'] = $currentDate->format('Y-m-d');
            
            if (isset($validated['reminder_time'])) {
                $reminderTime = Carbon::parse($validated['reminder_time']);
                $taskData['reminder_time'] = $currentDate->copy()
                    ->setTime($reminderTime->hour, $reminderTime->minute)
                    ->format('Y-m-d H:i:s');
            }

            $batchData[] = array_merge($taskData, [
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Increment date based on recurring type
            switch ($recurringType) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'biweekly':
                    $currentDate->addWeeks(2);
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
            }
        }

        // Bulk insert for better performance
        CalendarTask::insert($batchData);

        // Get the created tasks
        $createdTasks = CalendarTask::with(['workout:id,name'])
                                  ->forUser($this->getUserId())
                                  ->where('created_at', '>=', now()->subMinute())
                                  ->get();

        return $createdTasks->map(fn($task) => $this->enhanceTaskData($task))->toArray();
    }

    /**
     * Calculate month statistics efficiently
     */
    private function calculateMonthStats($tasks): array
    {
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('is_completed', true)->count();
        $workoutTasks = $tasks->where('task_type', 'workout');
        $totalWorkoutMinutes = $workoutTasks->sum('duration') ?: 0;

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $totalTasks - $completedTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0,
            'total_workouts' => $workoutTasks->count(),
            'total_workout_hours' => round($totalWorkoutMinutes / 60, 1),
            'tasks_by_type' => [
                'workout' => $tasks->where('task_type', 'workout')->count(),
                'goal' => $tasks->where('task_type', 'goal')->count(),
                'rest' => $tasks->where('task_type', 'rest')->count(),
                'nutrition' => $tasks->where('task_type', 'nutrition')->count(),
                'reminder' => $tasks->where('task_type', 'reminder')->count(),
                'other' => $tasks->where('task_type', 'other')->count(),
            ],
            'tasks_by_priority' => [
                'high' => $tasks->where('priority', 'high')->count(),
                'medium' => $tasks->where('priority', 'medium')->count(),
                'low' => $tasks->where('priority', 'low')->count(),
            ]
        ];
    }

    /**
     * Calculate day statistics efficiently
     */
    private function calculateDayStats($tasks): array
    {
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('is_completed', true)->count();

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $totalTasks - $completedTasks,
            'workouts' => $tasks->where('task_type', 'workout')->count(),
            'goals' => $tasks->where('task_type', 'goal')->count(),
            'completion_rate' => $totalTasks > 0 
                ? round(($completedTasks / $totalTasks) * 100, 1)
                : 0
        ];
    }

    /**
     * Calculate week statistics efficiently
     */
    private function calculateWeekStats($tasks): array
    {
        $stats = $this->calculateDayStats($tasks);
        $tasksByDay = $tasks->groupBy(fn($task) => $task->task_date->format('Y-m-d'));
        
        return array_merge($stats, [
            'days_with_tasks' => $tasksByDay->count()
        ]);
    }
}