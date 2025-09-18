<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserScore;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends BaseController
{
    public function getUserScore(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            $userScore = $user->userScore ?? UserScore::createOrUpdateForUser($user);
            
            return $this->successResponse([
                'total_points' => $userScore->total_points,
                'current_streak' => $userScore->current_streak,
                'best_streak' => $userScore->best_streak,
                'goals_completed' => $userScore->goals_completed,
                'goals_created' => $userScore->goals_created,
                'achievements_unlocked' => $userScore->achievements_unlocked,
                'level' => $userScore->level,
                'level_progress' => $userScore->level_progress,
                'next_level_points' => $userScore->getNextLevelPoints(),
                'points_to_next_level' => $userScore->getPointsToNextLevel(),
                'weekly_goals_completed' => $userScore->weekly_goals_completed,
                'monthly_goals_completed' => $userScore->monthly_goals_completed,
                'ranking' => UserScore::getUserRanking($user->id)
            ], 'User score retrieved successfully');
        }, 'Get User Score');
    }

    public function getLeaderboard(Request $request)
    {
        return $this->execute(function () use ($request) {
            $limit = $request->get('limit', 10);
            $topUsers = UserScore::getTopUsers($limit);
            
            $leaderboard = $topUsers->map(function($userScore, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $userScore->user->id,
                        'name' => $userScore->user->name,
                        'first_name' => $userScore->user->first_name,
                    ],
                    'total_points' => $userScore->total_points,
                    'level' => $userScore->level,
                    'current_streak' => $userScore->current_streak,
                    'goals_completed' => $userScore->goals_completed,
                    'achievements_unlocked' => $userScore->achievements_unlocked
                ];
            });
            
            return $this->successResponse($leaderboard, 'Leaderboard retrieved successfully');
        }, 'Get Leaderboard');
    }
}







