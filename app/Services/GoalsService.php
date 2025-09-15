<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GoalsService
{
    public function getGoals(User $user, array $filters = []): array
    {
        $query = Goal::where('user_id', $user->id);

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $goals = $query->orderBy('created_at', 'desc')->get();

        return $goals->map(function($goal) {
            return $goal->toArray();
        })->toArray();
    }

    public function createGoal(User $user, array $data): Goal
    {
        $data['user_id'] = $user->id;
        $data['current_value'] = 0;
        $data['status'] = 'active';

        return Goal::create($data);
    }

    public function updateGoal(User $user, int $goalId, array $data): Goal
    {
        $goal = Goal::where('user_id', $user->id)->findOrFail($goalId);
        $goal->update($data);
        return $goal;
    }

    public function getGoal(User $user, int $goalId): Goal
    {
        return Goal::where('user_id', $user->id)->findOrFail($goalId);
    }

    public function deleteGoal(User $user, int $goalId): void
    {
        $goal = Goal::where('user_id', $user->id)->findOrFail($goalId);
        $goal->delete();
    }

    public function updateGoalProgress(User $user, int $goalId, float $progressValue): Goal
    {
        $goal = Goal::where('user_id', $user->id)->findOrFail($goalId);
        $goal->updateProgress($progressValue);
        return $goal;
    }

    public function markGoalComplete(User $user, int $goalId): Goal
    {
        $goal = Goal::where('user_id', $user->id)->findOrFail($goalId);
        $goal->markAsCompleted();
        return $goal;
    }

    public function activateGoal(User $user, int $goalId): Goal
    {
        $goal = Goal::where('user_id', $user->id)->findOrFail($goalId);
        $goal->status = 'active';
        $goal->save();
        return $goal;
    }

    public function pauseGoal(User $user, int $goalId): Goal
    {
        $goal = Goal::where('user_id', $user->id)->findOrFail($goalId);
        $goal->status = 'paused';
        $goal->save();
        return $goal;
    }

    public function resetGoal(User $user, int $goalId): Goal
    {
        $goal = Goal::where('user_id', $user->id)->findOrFail($goalId);
        $goal->update([
            'current_value' => 0,
            'status' => 'not-started'
        ]);
        return $goal;
    }

    public function resetAllGoals(User $user): void
    {
        Goal::where('user_id', $user->id)->update([
            'current_value' => 0,
            'status' => 'not-started',
        ]);
    }

    public function validateGoalData(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'title' => ($isUpdate ? 'sometimes|' : '') . 'required|string|max:255',
            'description' => 'nullable|string',
            'target_value' => ($isUpdate ? 'sometimes|' : '') . 'required|numeric|min:0',
            'unit' => ($isUpdate ? 'sometimes|' : '') . 'required|string|max:50',
            'target_date' => 'nullable|date',
            'category' => 'nullable|string|max:100'
        ];

        if ($isUpdate) {
            $rules['current_value'] = 'sometimes|numeric|min:0';
            $rules['status'] = 'sometimes|in:not-started,active,completed,paused';
        }

        return $request->validate($rules);
    }
}

