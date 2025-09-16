<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Achievement;
use App\Models\UserScore;
use Illuminate\Support\Facades\Log;

class AchievementController extends BaseController
{
    public function index(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            $achievements = Achievement::active()->ordered()->get();
            
            $achievementsWithProgress = $achievements->map(function($achievement) use ($user) {
                $userHasAchievement = $user->achievements()->where('achievement_id', $achievement->id)->first();
                $progress = $achievement->getUserProgress($user);
                
                return array_merge($achievement->toArray(), [
                    'unlocked' => (bool) $userHasAchievement,
                    'unlocked_at' => $userHasAchievement?->pivot?->unlocked_at,
                    'points_earned' => $userHasAchievement?->pivot?->points_earned ?? 0,
                    'progress' => $progress
                ]);
            });
            
            return $this->successResponse($achievementsWithProgress, 'Achievements retrieved successfully');
        }, 'Get Achievements');
    }

    public function check(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            $newAchievements = Achievement::checkAllForUser($user);
            
            return $this->successResponse($newAchievements, count($newAchievements) . ' new achievements unlocked!');
        }, 'Check Achievements');
    }
}





