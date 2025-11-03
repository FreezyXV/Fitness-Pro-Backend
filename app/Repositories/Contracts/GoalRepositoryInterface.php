<?php

namespace App\Repositories\Contracts;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Collection;

interface GoalRepositoryInterface extends RepositoryInterface
{
    /**
     * Get goals for a user with optional filters.
     */
    public function getForUser(User $user, array $filters = []): Collection;

    /**
     * Find a goal by ID for a specific user.
     */
    public function findForUser(User $user, int $goalId): ?Goal;

    /**
     * Find a goal by ID for a specific user or fail.
     */
    public function findOrFailForUser(User $user, int $goalId): Goal;

    /**
     * Reset all goals for a specific user.
     */
    public function resetAllForUser(User $user): int;

    /**
     * Count goals for a user.
     */
    public function countForUser(User $user): int;

    /**
     * Retrieve template goals.
     */
    public function getTemplates(): Collection;
}
