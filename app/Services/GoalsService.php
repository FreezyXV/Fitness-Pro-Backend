<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\User;
use App\Repositories\Contracts\GoalRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GoalsService
{
    protected GoalRepositoryInterface $goalRepository;

    public function __construct(GoalRepositoryInterface $goalRepository)
    {
        $this->goalRepository = $goalRepository;
    }

    public function getGoals(User $user, array $filters = []): array
    {
        $goals = $this->goalRepository->getForUser($user, $filters);

        return $goals->map(function($goal) {
            return $goal->toArray();
        })->toArray();
    }

    public function createGoal(User $user, array $data): Goal
    {
        $data['user_id'] = $user->id;
        $data['current_value'] = 0;
        $data['status'] = 'active';

        return $this->goalRepository->create($data);
    }

    public function updateGoal(User $user, int $goalId, array $data): Goal
    {
        $goal = $this->goalRepository->findOrFailForUser($user, $goalId);
        return $this->goalRepository->update($goal, $data);
    }

    public function getGoal(User $user, int $goalId): Goal
    {
        return $this->goalRepository->findOrFailForUser($user, $goalId);
    }

    public function deleteGoal(User $user, int $goalId): void
    {
        $goal = $this->goalRepository->findOrFailForUser($user, $goalId);
        $this->goalRepository->delete($goal);
    }

    public function updateGoalProgress(User $user, int $goalId, float $progressValue): Goal
    {
        $goal = $this->goalRepository->findOrFailForUser($user, $goalId);
        $goal->updateProgress($progressValue);
        return $goal;
    }

    public function markGoalComplete(User $user, int $goalId): Goal
    {
        $goal = $this->goalRepository->findOrFailForUser($user, $goalId);
        $goal->markAsCompleted();
        return $goal;
    }

    public function activateGoal(User $user, int $goalId): Goal
    {
        $goal = $this->goalRepository->findOrFailForUser($user, $goalId);
        $goal->status = 'active';
        $goal->save();
        return $goal;
    }

    public function pauseGoal(User $user, int $goalId): Goal
    {
        $goal = $this->goalRepository->findOrFailForUser($user, $goalId);
        $goal->status = 'paused';
        $goal->save();
        return $goal;
    }

    public function resetGoal(User $user, int $goalId): Goal
    {
        $goal = $this->goalRepository->findOrFailForUser($user, $goalId);
        return $this->goalRepository->update($goal, [
            'current_value' => 0,
            'status' => 'not-started'
        ]);
    }

    public function resetAllGoals(User $user): void
    {
        $this->goalRepository->resetAllForUser($user);
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
