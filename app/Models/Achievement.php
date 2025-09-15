<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'points',
        'category',
        'rarity',
        'requirements',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'requirements' => 'array',
        'is_active' => 'boolean',
        'points' => 'integer',
        'sort_order' => 'integer'
    ];

    // Relations
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot(['unlocked_at', 'progress_data', 'points_earned'])
            ->withTimestamps();
    }

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Achievement checking logic
    public function checkRequirements(User $user): bool
    {
        if (!$this->is_active || !$this->requirements) {
            return false;
        }

        $userScore = $user->userScore;
        if (!$userScore) {
            return false;
        }

        foreach ($this->requirements as $requirement => $value) {
            if (!$this->checkSingleRequirement($requirement, $value, $userScore, $user)) {
                return false;
            }
        }

        return true;
    }

    private function checkSingleRequirement(string $requirement, $value, UserScore $userScore, User $user): bool
    {
        switch ($requirement) {
            case 'goals_completed':
                return $userScore->goals_completed >= $value;
            
            case 'goals_created':
                return $userScore->goals_created >= $value;
            
            case 'current_streak':
                return $userScore->current_streak >= $value;
            
            case 'best_streak':
                return $userScore->best_streak >= $value;
            
            case 'total_points':
                return $userScore->total_points >= $value;
            
            case 'level':
                return $userScore->level >= $value;
            
            case 'weekly_goals_completed':
                return $userScore->weekly_goals_completed >= $value;
            
            case 'monthly_goals_completed':
                return $userScore->monthly_goals_completed >= $value;
            
            case 'goals_in_category':
                // Count goals completed in specific category
                $categoryCount = $user->goals()
                    ->where('status', 'completed')
                    ->where('category', $value['category'])
                    ->count();
                return $categoryCount >= $value['count'];
            
            case 'perfect_week':
                // Check if user completed goals every day for a week
                // This would need more complex logic with daily tracking
                return false; // Placeholder
            
            case 'milestone_progress':
                // Check milestone-based achievements
                $milestoneData = $userScore->milestone_data ?? [];
                return isset($milestoneData[$value['milestone']]) && 
                       $milestoneData[$value['milestone']] >= $value['target'];
            
            default:
                return false;
        }
    }

    // Award achievement to user
    public function awardToUser(User $user, array $progressData = []): UserAchievement
    {
        // Check if user already has this achievement
        $existing = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $this->id)
            ->first();
        
        if ($existing) {
            return $existing;
        }

        // Create user achievement record
        $userAchievement = UserAchievement::create([
            'user_id' => $user->id,
            'achievement_id' => $this->id,
            'progress_data' => $progressData,
            'points_earned' => $this->points,
            'unlocked_at' => now()
        ]);

        // Update user score
        $userScore = $user->userScore ?? UserScore::createOrUpdateForUser($user);
        $userScore->addPoints($this->points, "Achievement: {$this->name}");
        $userScore->achievements_unlocked++;
        $userScore->save();

        // Fire achievement unlocked event (uncomment when event is created)
        // event(new \App\Events\AchievementUnlocked($user, $this, $userAchievement));

        return $userAchievement;
    }

    // Get user's progress towards this achievement
    public function getUserProgress(User $user): array
    {
        $userScore = $user->userScore;
        if (!$userScore || !$this->requirements) {
            return ['progress' => 0, 'total' => 1, 'percentage' => 0];
        }

        $progress = [];
        $totalRequirements = count($this->requirements);
        $metRequirements = 0;

        foreach ($this->requirements as $requirement => $value) {
            $current = $this->getCurrentRequirementValue($requirement, $userScore, $user);
            $target = is_array($value) ? $value['count'] ?? $value['target'] ?? 1 : $value;
            
            $progress[$requirement] = [
                'current' => $current,
                'target' => $target,
                'met' => $current >= $target,
                'percentage' => $target > 0 ? min(100, ($current / $target) * 100) : 0
            ];
            
            if ($progress[$requirement]['met']) {
                $metRequirements++;
            }
        }

        return [
            'progress' => $metRequirements,
            'total' => $totalRequirements,
            'percentage' => $totalRequirements > 0 ? ($metRequirements / $totalRequirements) * 100 : 0,
            'requirements' => $progress
        ];
    }

    private function getCurrentRequirementValue(string $requirement, UserScore $userScore, User $user)
    {
        switch ($requirement) {
            case 'goals_completed':
                return $userScore->goals_completed;
            case 'goals_created':
                return $userScore->goals_created;
            case 'current_streak':
                return $userScore->current_streak;
            case 'best_streak':
                return $userScore->best_streak;
            case 'total_points':
                return $userScore->total_points;
            case 'level':
                return $userScore->level;
            case 'weekly_goals_completed':
                return $userScore->weekly_goals_completed;
            case 'monthly_goals_completed':
                return $userScore->monthly_goals_completed;
            default:
                return 0;
        }
    }

    // Static methods for achievement management
    public static function checkAllForUser(User $user): array
    {
        $unlockedAchievements = [];
        $achievements = static::active()->get();
        
        foreach ($achievements as $achievement) {
            // Skip if user already has this achievement
            if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
                continue;
            }
            
            if ($achievement->checkRequirements($user)) {
                $userAchievement = $achievement->awardToUser($user);
                $unlockedAchievements[] = $userAchievement;
            }
        }
        
        return $unlockedAchievements;
    }

    // Get achievement color based on rarity
    public function getRarityColor(): string
    {
        return match ($this->rarity) {
            'common' => '#6B7280',
            'rare' => '#3B82F6',
            'epic' => '#8B5CF6',
            'legendary' => '#F59E0B',
            default => '#6B7280'
        };
    }

    // Get achievement border style based on rarity
    public function getRarityBorder(): string
    {
        return match ($this->rarity) {
            'common' => 'border-gray-500',
            'rare' => 'border-blue-500',
            'epic' => 'border-purple-500',
            'legendary' => 'border-yellow-500',
            default => 'border-gray-500'
        };
    }
}