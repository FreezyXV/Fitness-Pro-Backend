<?php
//app/Http/Controllers/GoalController.php - SIMPLIFIED VERSION

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
class GoalController extends BaseController
{
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

            // Check if goals table exists
            if (!\Schema::hasTable('goals')) {
                \Log::warning('Goals table does not exist');
                return $this->successResponse([], 'Goals table not available');
            }

            $query = Goal::where('user_id', $user->id);
            
            // Apply status filter if provided
            if ($request->has('status') && $request->get('status') !== '') {
                $query->where('status', $request->get('status'));
            }
            
            $goals = $query->orderBy('created_at', 'desc')->get();

            // Convert to array with computed fields
            $goalsArray = $goals->map(function($goal) {
                return $goal->toArray(); // This will include computed fields
            })->toArray();

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

            // Check if goals table exists
            if (!\Schema::hasTable('goals')) {
                return $this->errorResponse('Goals feature not available', 503);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'target_value' => 'required|numeric|min:0',
                'unit' => 'required|string|max:50',
                'target_date' => 'nullable|date',
                'category' => 'nullable|string|max:100'
            ]);

            $validated['user_id'] = $user->id;
            $validated['current_value'] = 0;
            $validated['status'] = 'active';

            $goal = Goal::create($validated);
            
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

            $goal = Goal::where('user_id', $user->id)->findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'target_value' => 'sometimes|numeric|min:0',
                'current_value' => 'sometimes|numeric|min:0',
                'unit' => 'sometimes|string|max:50',
                'target_date' => 'nullable|date',
                'status' => 'sometimes|in:active,completed,paused',
                'category' => 'nullable|string|max:100'
            ]);

            $goal->update($validated);
            
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
            $goal = Goal::where('user_id', $user->id)->findOrFail($id);
            
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
            $goal = Goal::where('user_id', $user->id)->findOrFail($id);
            
            $goal->delete();
            
            return $this->successResponse(null, 'Goal deleted successfully');

        } catch (\Exception $e) {
            \Log::error('Goal deletion error: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete goal: ' . $e->getMessage(), 500);
        }
    }
}
