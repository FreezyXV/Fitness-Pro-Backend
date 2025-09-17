<?php
//app/Http/Controllers/GoalController.php - SIMPLIFIED VERSION

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\GoalsService; // Import the new service
class GoalController extends BaseController
{
    protected $goalsService;

    public function __construct(GoalsService $goalsService)
    {
        $this->goalsService = $goalsService;
    }

    /**
     * Get goals - simplified
     */
    public function index(Request $request)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            
            \Log::info('Goals request for user: ' . $user->id);

            $filters = $request->only(['status', 'category', 'priority', 'searchTerm']);
            $goalsArray = $this->goalsService->getGoals($user, $filters);

            \Log::info('Goals retrieved successfully: ' . count($goalsArray));

            return $this->successResponse($goalsArray, 'Goals retrieved successfully');

        } catch (\Exception $e) {
            \Log::error('Goals error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return $this->errorResponse('Failed to get goals: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create goal - simplified
     */
    public function store(Request $request)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();

            $validated = $this->goalsService->validateGoalData($request);
            $goal = $this->goalsService->createGoal($user, $validated);
            
            return $this->createdResponse($goal->toArray(), 'Goal created successfully');

        } catch (\Exception $e) {
            \Log::error('Goal creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create goal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update goal - simplified
     */
    public function update(Request $request, $id)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();

            $validated = $this->goalsService->validateGoalData($request, true);
            $goal = $this->goalsService->updateGoal($user, (int) $id, $validated);
            
            return $this->successResponse($goal->toArray(), 'Goal updated successfully');

        } catch (\Exception $e) {
            \Log::error('Goal update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update goal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show specific goal
     */
    public function show($id)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            $goal = $this->goalsService->getGoal($user, (int) $id);
            
            return $this->successResponse($goal->toArray(), 'Goal retrieved successfully');

        } catch (\Exception $e) {
            \Log::error('Goal show error: ' . $e->getMessage());
            return $this->errorResponse('Failed to get goal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete goal
     */
    public function destroy($id)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            $this->goalsService->deleteGoal($user, (int) $id);
            
            return $this->successResponse(null, 'Goal deleted successfully');

        } catch (\Exception $e) {
            \Log::error('Goal deletion error: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete goal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update goal progress
     */
    public function updateProgress(Request $request, $id)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            $validated = $request->validate([
                'progress_value' => 'required|numeric|min:0',
            ]);
            $goal = $this->goalsService->updateGoalProgress($user, (int) $id, $validated['progress_value']);
            
            return $this->successResponse($goal->toArray(), 'Goal progress updated successfully');

        } catch (\Exception $e) {
            \Log::error('Goal progress update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update goal progress: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark goal as complete
     */
    public function markComplete($id)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            $goal = $this->goalsService->markGoalComplete($user, (int) $id);
            
            return $this->successResponse($goal->toArray(), 'Goal marked as complete');

        } catch (\Exception $e) {
            \Log::error('Mark goal complete error: ' . $e->getMessage());
            return $this->errorResponse('Failed to mark goal complete: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Activate goal
     */
    public function activate($id)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            $goal = $this->goalsService->activateGoal($user, (int) $id);
            
            return $this->successResponse($goal->toArray(), 'Goal activated');

        } catch (\Exception $e) {
            \Log::error('Activate goal error: ' . $e->getMessage());
            return $this->errorResponse('Failed to activate goal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Pause goal
     */
    public function pause($id)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            $goal = $this->goalsService->pauseGoal($user, (int) $id);
            
            return $this->successResponse($goal->toArray(), 'Goal paused');

        } catch (\Exception $e) {
            \Log::error('Pause goal error: ' . $e->getMessage());
            return $this->errorResponse('Failed to pause goal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset specific goal status and progress
     */
    public function resetGoalStatus($id)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            $goal = $this->goalsService->resetGoal($user, (int) $id);
            
            return $this->successResponse($goal->toArray(), 'Goal reset successfully');

        } catch (\Exception $e) {
            \Log::error('Goal reset error: ' . $e->getMessage());
            return $this->errorResponse('Failed to reset goal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset all goals for the authenticated user.
     */
    public function resetAllGoals()
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            $this->goalsService->resetAllGoals($user);

            return $this->successResponse(null, 'All goals reset successfully');
        } catch (\Exception $e) {
            \Log::error('Reset all goals error: ' . $e->getMessage());
            return $this->errorResponse('Failed to reset all goals: ' . $e->getMessage(), 500);
        }
    }

    // Removed validateGoalData as it's now in GoalsService
}
