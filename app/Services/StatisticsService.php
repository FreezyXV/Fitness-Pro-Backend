<?php
// app/Services/StatisticsService.php - OPTIMIZED AND MERGED VERSION
namespace App\Services;

use App\Models\User;
use App\Models\WorkoutSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StatisticsService
{
    protected StreakCalculatorService $streakCalculator;

    public function __construct(StreakCalculatorService $streakCalculator)
    {
        $this->streakCalculator = $streakCalculator;
    }

    /**
     * Get comprehensive user workout statistics with caching
     */
    public function getUserStats(User $user): array
    {
        return Cache::remember("user_complete_stats_{$user->id}", 900, function () use ($user) {
            $sessions = $user->workoutSessions()->where('status', 'completed');
            $consistencyStats = $this->streakCalculator->calculateConsistencyStats($user);
            
            $baseStats = [
                'total_sessions' => $sessions->count(),
                'total_minutes' => $sessions->sum('duration_minutes') ?? 0,
                'total_calories' => $sessions->sum('calories_burned') ?? 0,
                'average_duration' => round($sessions->avg('duration_minutes') ?? 0, 1),
                'average_calories' => round($sessions->avg('calories_burned') ?? 0, 1),
                'this_week' => $this->getWeeklyCount($user),
                'this_month' => $this->getMonthlyCount($user),
                'longest_session' => $sessions->max('duration_minutes') ?? 0,
                'most_calories' => $sessions->max('calories_burned') ?? 0,
            ];

            return array_merge($baseStats, ['consistency' => $consistencyStats]);
        });
    }

    /**
     * Get weekly workout data for charts (optimized)
     */
    public function getWeeklyData(User $user): array
    {
        return Cache::remember("user_weekly_data_{$user->id}", 600, function () use ($user) {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            
            // Single query to get all sessions for the week
            $sessions = $user->workoutSessions()
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->selectRaw('DATE(completed_at) as date, COUNT(*) as sessions, SUM(duration_minutes) as minutes, SUM(calories_burned) as calories')
                ->groupBy('date')
                ->get()
                ->keyBy('date');
            
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dateString = $date->format('Y-m-d');
                $sessionData = $sessions->get($dateString);
                
                $data[] = [
                    'date' => $dateString,
                    'day' => $date->format('l'),
                    'sessions' => $sessionData->sessions ?? 0,
                    'minutes' => $sessionData->minutes ?? 0,
                    'calories' => $sessionData->calories ?? 0
                ];
            }
            
            return $data;
        });
    }

    /**
     * Get monthly overview with comparison (optimized)
     */
    public function getMonthlyOverview(User $user): array
    {
        return Cache::remember("user_monthly_overview_{$user->id}", 1800, function () use ($user) {
            $currentMonth = Carbon::now();
            $previousMonth = Carbon::now()->subMonth();
            
            $currentData = $this->getMonthData($user, $currentMonth);
            $previousData = $this->getMonthData($user, $previousMonth);
            
            return [
                'current_month' => array_merge($currentData, [
                    'name' => $currentMonth->format('F Y')
                ]),
                'previous_month' => array_merge($previousData, [
                    'name' => $previousMonth->format('F Y')
                ]),
                'comparison' => [
                    'sessions_change' => $this->calculatePercentageChange(
                        $previousData['sessions'], 
                        $currentData['sessions']
                    ),
                    'minutes_change' => $this->calculatePercentageChange(
                        $previousData['minutes'], 
                        $currentData['minutes']
                    ),
                    'calories_change' => $this->calculatePercentageChange(
                        $previousData['calories'], 
                        $currentData['calories']
                    ),
                ]
            ];
        });
    }

    /**
     * Check if user has achieved any milestones (optimized)
     */
    public function checkMilestones(User $user): array
    {
        return Cache::remember("user_milestones_{$user->id}", 3600, function () use ($user) {
            // Get all stats in one query
            $stats = $user->workoutSessions()
                ->where('status', 'completed')
                ->selectRaw('COUNT(*) as total_sessions, SUM(calories_burned) as total_calories')
                ->first();
            
            $currentStreak = $this->streakCalculator->calculateWorkoutStreak($user);
            
            $milestones = [];
            
            // Session milestones
            $sessionMilestones = [1, 5, 10, 25, 50, 100, 250, 500];
            foreach ($sessionMilestones as $milestone) {
                if ($stats->total_sessions >= $milestone) {
                    $milestones[] = [
                        'type' => 'sessions',
                        'value' => $milestone,
                        'achieved' => true,
                        'title' => "{$milestone} séances complétées"
                    ];
                }
            }
            
            // Streak milestones
            $streakMilestones = [3, 7, 14, 30, 60, 100];
            foreach ($streakMilestones as $milestone) {
                if ($currentStreak >= $milestone) {
                    $milestones[] = [
                        'type' => 'streak',
                        'value' => $milestone,
                        'achieved' => true,
                        'title' => "{$milestone} jours consécutifs"
                    ];
                }
            }
            
            // Calorie milestones
            $calorieMilestones = [1000, 5000, 10000, 25000, 50000];
            foreach ($calorieMilestones as $milestone) {
                if ($stats->total_calories >= $milestone) {
                    $milestones[] = [
                        'type' => 'calories',
                        'value' => $milestone,
                        'achieved' => true,
                        'title' => "{$milestone} calories brûlées"
                    ];
                }
            }
            
            return $milestones;
        });
    }

    /**
     * Get workout consistency score (0-100) with caching
     */
    public function getConsistencyScore(User $user, int $days = 30): float
    {
        return Cache::remember("user_consistency_{$user->id}_{$days}", 600, function () use ($user, $days) {
            $workoutDays = $user->workoutSessions()
                ->where('status', 'completed')
                ->whereBetween('completed_at', [
                    Carbon::now()->subDays($days),
                    Carbon::now()
                ])
                ->selectRaw('COUNT(DISTINCT DATE(completed_at)) as unique_days')
                ->value('unique_days');
            
            return round(($workoutDays / $days) * 100, 1);
        });
    }

    /**
     * Get performance trends over time
     */
    public function getPerformanceTrends(User $user, int $months = 6): array
    {
        return Cache::remember("user_trends_{$user->id}_{$months}", 3600, function () use ($user, $months) {
            $trends = [];
            
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $monthData = $this->getMonthData($user, $month);
                
                $trends[] = array_merge($monthData, [
                    'month' => $month->format('Y-m'),
                    'month_name' => $month->format('F Y'),
                    'avg_session_duration' => $monthData['sessions'] > 0 
                        ? round($monthData['minutes'] / $monthData['sessions'], 1) 
                        : 0,
                    'avg_calories_per_session' => $monthData['sessions'] > 0 
                        ? round($monthData['calories'] / $monthData['sessions'], 1) 
                        : 0
                ]);
            }
            
            return $trends;
        });
    }

    /**
     * Get user ranking compared to other users (simplified)
     */
    public function getUserRanking(User $user): array
    {
        return Cache::remember("user_ranking_{$user->id}", 1800, function () use ($user) {
            $userStats = $this->getUserStats($user);
            
            // Get rankings for different metrics
            $totalSessionsRank = User::whereHas('workoutSessions', function ($q) use ($userStats) {
                $q->where('status', 'completed')
                  ->havingRaw('COUNT(*) > ?', [$userStats['total_sessions']]);
            })->count() + 1;
            
            $totalCaloriesRank = User::whereHas('workoutSessions', function ($q) use ($userStats) {
                $q->where('status', 'completed')
                  ->havingRaw('SUM(calories_burned) > ?', [$userStats['total_calories']]);
            })->count() + 1;
            
            $totalUsers = User::whereHas('workoutSessions')->count();
            
            return [
                'total_sessions_rank' => $totalSessionsRank,
                'total_calories_rank' => $totalCaloriesRank,
                'total_users' => $totalUsers,
                'percentile' => [
                    'sessions' => round((($totalUsers - $totalSessionsRank + 1) / $totalUsers) * 100, 1),
                    'calories' => round((($totalUsers - $totalCaloriesRank + 1) / $totalUsers) * 100, 1),
                ]
            ];
        });
    }

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(User $user): array
    {
        return Cache::remember("user_dashboard_stats_{$user->id}", 600, function () use ($user) {
            $baseStats = $this->getUserStats($user);
            $weeklyData = $this->getWeeklyData($user);
            $consistency = $this->getConsistencyScore($user);
            $milestones = $this->checkMilestones($user);
            
            return [
                'overview' => $baseStats,
                'weekly_data' => $weeklyData,
                'consistency_score' => $consistency,
                'recent_milestones' => array_slice($milestones, -3), // Last 3 milestones
                'next_milestone' => $this->getNextMilestone($user),
                'performance_trend' => $this->getPerformanceTrend($user),
            ];
        });
    }

    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================

    private function getWeeklyCount(User $user): int
    {
        return $user->workoutSessions()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->count();
    }

    private function getMonthlyCount(User $user): int
    {
        return $user->workoutSessions()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->count();
    }

    private function getMonthData(User $user, Carbon $month): array
    {
        $sessions = $user->workoutSessions()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth()
            ])
            ->selectRaw('COUNT(*) as sessions, SUM(duration_minutes) as minutes, SUM(calories_burned) as calories')
            ->first();
        
        return [
            'sessions' => $sessions->sessions ?? 0,
            'minutes' => $sessions->minutes ?? 0,
            'calories' => $sessions->calories ?? 0,
        ];
    }

    private function calculatePercentageChange(int $previous, int $current): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getNextMilestone(User $user): ?array
    {
        $totalSessions = $user->workoutSessions()->where('status', 'completed')->count();
        $milestones = [1, 5, 10, 25, 50, 100, 250, 500, 1000];
        
        foreach ($milestones as $milestone) {
            if ($totalSessions < $milestone) {
                return [
                    'type' => 'sessions',
                    'target' => $milestone,
                    'current' => $totalSessions,
                    'remaining' => $milestone - $totalSessions,
                    'progress' => round(($totalSessions / $milestone) * 100, 1)
                ];
            }
        }
        
        return null;
    }

    private function getPerformanceTrend(User $user): string
    {
        $thisMonth = $this->getMonthlyCount($user);
        $lastMonth = $user->workoutSessions()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth()
            ])
            ->count();
        
        if ($thisMonth > $lastMonth) {
            return 'improving';
        } elseif ($thisMonth < $lastMonth) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Clear user statistics cache
     */
    public function clearUserCache(User $user): void
    {
        $keys = [
            "user_complete_stats_{$user->id}",
            "user_weekly_data_{$user->id}",
            "user_monthly_overview_{$user->id}",
            "user_milestones_{$user->id}",
            "user_consistency_{$user->id}_30",
            "user_trends_{$user->id}_6",
            "user_ranking_{$user->id}",
            "user_dashboard_stats_{$user->id}",
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}