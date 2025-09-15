<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Traits\CamelCaseSerializationTrait;

class UserDiet extends Model
{
    use HasFactory, CamelCaseSerializationTrait;

    protected $fillable = [
        'user_id',
        'diet_id',
        'diet_name',
        'diet_type',
        'diet_config',
        'start_date',
        'end_date',
        'target_duration_days',
        'status',
        'current_score',
        'streak_days',
        'total_xp_earned',
        'daily_scores',
        'achievements_unlocked',
        'notes',
    ];

    protected $casts = [
        'diet_config' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'current_score' => 'decimal:2',
        'daily_scores' => 'array',
        'achievements_unlocked' => 'array',
    ];

    protected $appends = [
        'days_active',
        'completion_percentage',
        'average_daily_score',
        'is_active',
        'days_remaining'
    ];

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ===== ACCESSORS =====

    public function getDaysActiveAttribute(): int
    {
        if (!$this->start_date) return 0;
        
        $endDate = $this->status === 'active' ? now() : ($this->end_date ?? now());
        return $this->start_date->diffInDays($endDate) + 1;
    }

    public function getCompletionPercentageAttribute(): float
    {
        if (!$this->target_duration_days || $this->target_duration_days <= 0) {
            return 0;
        }
        
        return min(($this->days_active / $this->target_duration_days) * 100, 100);
    }

    public function getAverageDailyScoreAttribute(): float
    {
        if (!$this->daily_scores || empty($this->daily_scores)) {
            return 0;
        }
        
        $scores = array_filter($this->daily_scores, 'is_numeric');
        return empty($scores) ? 0 : round(array_sum($scores) / count($scores), 2);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->target_duration_days || $this->status !== 'active') {
            return 0;
        }
        
        return max(0, $this->target_duration_days - $this->days_active);
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('diet_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('start_date', '>=', now()->subDays($days));
    }

    // ===== METHODS =====

    /**
     * Record daily adherence score
     */
    public function recordDailyScore(float $score, string $date = null): void
    {
        $date = $date ?? now()->toDateString();
        $dailyScores = $this->daily_scores ?? [];
        $dailyScores[$date] = round($score, 2);
        
        // Keep only last 90 days to avoid too much data
        if (count($dailyScores) > 90) {
            $dates = array_keys($dailyScores);
            sort($dates);
            $dailyScores = array_slice($dailyScores, -90, null, true);
        }
        
        $this->daily_scores = $dailyScores;
        
        // Update current score (moving average of last 7 days)
        $recentScores = array_slice($dailyScores, -7, null, true);
        $this->current_score = count($recentScores) > 0 
            ? round(array_sum($recentScores) / count($recentScores), 2)
            : $score;
        
        // Update streak
        $this->updateStreak($score, $date);
        
        $this->save();
    }

    /**
     * Update streak based on daily score
     */
    private function updateStreak(float $score, string $date): void
    {
        $threshold = 75; // Minimum score to maintain streak
        
        if ($score >= $threshold) {
            // Check if this continues the streak
            $yesterday = Carbon::parse($date)->subDay()->toDateString();
            $yesterdayScore = $this->daily_scores[$yesterday] ?? null;
            
            if ($yesterdayScore === null || $yesterdayScore >= $threshold) {
                $this->streak_days += 1;
            } else {
                $this->streak_days = 1; // Start new streak
            }
        } else {
            $this->streak_days = 0; // Break streak
        }
    }

    /**
     * Add XP and check for achievements
     */
    public function addXP(int $xp, string $reason = ''): void
    {
        $this->total_xp_earned += $xp;
        
        // Check for streak-based achievements
        $this->checkStreakAchievements();
        
        // Check for XP-based achievements
        $this->checkXPAchievements();
        
        $this->save();
    }

    /**
     * Check and unlock streak-based achievements
     */
    private function checkStreakAchievements(): void
    {
        $achievements = $this->achievements_unlocked ?? [];
        $currentStreak = $this->streak_days;
        
        $streakMilestones = [
            7 => 'week_warrior',
            14 => 'consistency_champion',
            30 => 'monthly_master',
            60 => 'dedication_legend'
        ];
        
        foreach ($streakMilestones as $days => $achievementId) {
            if ($currentStreak >= $days && !in_array($achievementId, $achievements)) {
                $achievements[] = $achievementId;
                $this->addXP(50 * ($days / 7), "achievement_$achievementId");
            }
        }
        
        $this->achievements_unlocked = $achievements;
    }

    /**
     * Check and unlock XP-based achievements
     */
    private function checkXPAchievements(): void
    {
        $achievements = $this->achievements_unlocked ?? [];
        $totalXP = $this->total_xp_earned;
        
        $xpMilestones = [
            500 => 'nutrition_novice',
            1000 => 'healthy_habits',
            2500 => 'wellness_warrior',
            5000 => 'nutrition_master',
            10000 => 'lifestyle_legend'
        ];
        
        foreach ($xpMilestones as $xp => $achievementId) {
            if ($totalXP >= $xp && !in_array($achievementId, $achievements)) {
                $achievements[] = $achievementId;
            }
        }
        
        $this->achievements_unlocked = $achievements;
    }

    /**
     * Complete the diet
     */
    public function complete(string $reason = 'completed'): void
    {
        $this->status = 'completed';
        $this->end_date = now();
        
        // Award completion bonus XP
        $completionBonus = min(500, $this->days_active * 10);
        $this->addXP($completionBonus, 'diet_completion');
        
        // Add completion achievement
        $achievements = $this->achievements_unlocked ?? [];
        if (!in_array('diet_completed', $achievements)) {
            $achievements[] = 'diet_completed';
            $this->achievements_unlocked = $achievements;
        }
        
        $this->save();
    }

    /**
     * Pause the diet
     */
    public function pause(string $reason = ''): void
    {
        $this->status = 'paused';
        if ($reason) {
            $this->notes = ($this->notes ?? '') . "\nPaused: $reason (" . now()->toDateString() . ")";
        }
        $this->save();
    }

    /**
     * Resume the diet
     */
    public function resume(): void
    {
        if ($this->status === 'paused') {
            $this->status = 'active';
            $this->notes = ($this->notes ?? '') . "\nResumed: " . now()->toDateString();
            $this->save();
        }
    }

    /**
     * Abandon the diet
     */
    public function abandon(string $reason = ''): void
    {
        $this->status = 'abandoned';
        $this->end_date = now();
        if ($reason) {
            $this->notes = ($this->notes ?? '') . "\nAbandoned: $reason (" . now()->toDateString() . ")";
        }
        $this->save();
    }

    /**
     * Get detailed progress statistics
     */
    public function getProgressStats(): array
    {
        return [
            'basic_stats' => [
                'days_active' => $this->days_active,
                'completion_percentage' => $this->completion_percentage,
                'current_score' => $this->current_score,
                'streak_days' => $this->streak_days,
                'total_xp' => $this->total_xp_earned,
            ],
            'performance_trends' => $this->getPerformanceTrends(),
            'achievements' => $this->getAchievementDetails(),
            'weekly_averages' => $this->getWeeklyAverages(),
            'milestone_progress' => $this->getMilestoneProgress(),
            'consistency_metrics' => $this->getConsistencyMetrics()
        ];
    }

    /**
     * Calculate performance trends
     */
    private function getPerformanceTrends(): array
    {
        if (!$this->daily_scores || count($this->daily_scores) < 7) {
            return ['trend' => 'insufficient_data'];
        }
        
        $scores = array_values($this->daily_scores);
        $recent = array_slice($scores, -7);
        $previous = array_slice($scores, -14, 7);
        
        $recentAvg = array_sum($recent) / count($recent);
        $previousAvg = count($previous) > 0 ? array_sum($previous) / count($previous) : $recentAvg;
        
        $trend = $recentAvg - $previousAvg;
        
        return [
            'trend' => $trend > 5 ? 'improving' : ($trend < -5 ? 'declining' : 'stable'),
            'trend_value' => round($trend, 2),
            'recent_average' => round($recentAvg, 2),
            'previous_average' => round($previousAvg, 2)
        ];
    }

    /**
     * Get achievement details with descriptions
     */
    private function getAchievementDetails(): array
    {
        $achievementDescriptions = [
            'week_warrior' => ['name' => 'Guerrier de la Semaine', 'description' => '7 jours d\'adhérence consécutifs'],
            'consistency_champion' => ['name' => 'Champion de Consistance', 'description' => '14 jours d\'adhérence consécutifs'],
            'monthly_master' => ['name' => 'Maître du Mois', 'description' => '30 jours d\'adhérence consécutifs'],
            'dedication_legend' => ['name' => 'Légende de Dévouement', 'description' => '60 jours d\'adhérence consécutifs'],
            'nutrition_novice' => ['name' => 'Novice en Nutrition', 'description' => '500 XP gagnés'],
            'healthy_habits' => ['name' => 'Habitudes Saines', 'description' => '1000 XP gagnés'],
            'wellness_warrior' => ['name' => 'Guerrier du Bien-être', 'description' => '2500 XP gagnés'],
            'nutrition_master' => ['name' => 'Maître en Nutrition', 'description' => '5000 XP gagnés'],
            'lifestyle_legend' => ['name' => 'Légende du Mode de Vie', 'description' => '10000 XP gagnés'],
            'diet_completed' => ['name' => 'Régime Terminé', 'description' => 'A complété un régime entier']
        ];
        
        $unlockedAchievements = [];
        foreach ($this->achievements_unlocked ?? [] as $achievementId) {
            if (isset($achievementDescriptions[$achievementId])) {
                $unlockedAchievements[] = array_merge(
                    ['id' => $achievementId],
                    $achievementDescriptions[$achievementId]
                );
            }
        }
        
        return $unlockedAchievements;
    }

    /**
     * Calculate weekly averages
     */
    private function getWeeklyAverages(): array
    {
        if (!$this->daily_scores || count($this->daily_scores) < 7) {
            return [];
        }
        
        $weeklyAverages = [];
        $scores = $this->daily_scores;
        $dates = array_keys($scores);
        sort($dates);
        
        // Group by weeks
        $weeks = [];
        foreach ($dates as $date) {
            $weekStart = Carbon::parse($date)->startOfWeek()->toDateString();
            $weeks[$weekStart][] = $scores[$date];
        }
        
        foreach ($weeks as $weekStart => $weekScores) {
            $weeklyAverages[] = [
                'week_start' => $weekStart,
                'average_score' => round(array_sum($weekScores) / count($weekScores), 2),
                'days_tracked' => count($weekScores)
            ];
        }
        
        return array_slice($weeklyAverages, -8); // Last 8 weeks
    }

    /**
     * Get milestone progress
     */
    private function getMilestoneProgress(): array
    {
        $milestones = [
            ['type' => 'duration', 'target' => 7, 'current' => $this->days_active, 'label' => '1 semaine'],
            ['type' => 'duration', 'target' => 30, 'current' => $this->days_active, 'label' => '1 mois'],
            ['type' => 'duration', 'target' => 60, 'current' => $this->days_active, 'label' => '2 mois'],
            ['type' => 'streak', 'target' => 7, 'current' => $this->streak_days, 'label' => 'Série de 7 jours'],
            ['type' => 'streak', 'target' => 14, 'current' => $this->streak_days, 'label' => 'Série de 2 semaines'],
            ['type' => 'xp', 'target' => 1000, 'current' => $this->total_xp_earned, 'label' => '1000 XP'],
            ['type' => 'xp', 'target' => 2500, 'current' => $this->total_xp_earned, 'label' => '2500 XP'],
        ];
        
        return array_map(function ($milestone) {
            $milestone['progress_percentage'] = min(($milestone['current'] / $milestone['target']) * 100, 100);
            $milestone['completed'] = $milestone['current'] >= $milestone['target'];
            return $milestone;
        }, $milestones);
    }

    /**
     * Calculate consistency metrics
     */
    private function getConsistencyMetrics(): array
    {
        if (!$this->daily_scores || count($this->daily_scores) < 3) {
            return ['insufficient_data' => true];
        }
        
        $scores = array_values($this->daily_scores);
        $mean = array_sum($scores) / count($scores);
        
        // Calculate standard deviation
        $variance = array_sum(array_map(function($score) use ($mean) {
            return pow($score - $mean, 2);
        }, $scores)) / count($scores);
        
        $standardDeviation = sqrt($variance);
        
        // Consistency score (lower deviation = higher consistency)
        $consistencyScore = max(0, 100 - ($standardDeviation * 2));
        
        return [
            'consistency_score' => round($consistencyScore, 2),
            'standard_deviation' => round($standardDeviation, 2),
            'average_score' => round($mean, 2),
            'score_range' => [
                'min' => min($scores),
                'max' => max($scores)
            ],
            'days_above_average' => count(array_filter($scores, function($score) use ($mean) {
                return $score > $mean;
            }))
        ];
    }
}