<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Achievement;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            // Goal-based achievements
            [
                'key' => 'first_goal',
                'name' => 'First Steps',
                'description' => 'Create your first goal',
                'icon' => '🎯',
                'points' => 10,
                'category' => 'goals',
                'rarity' => 'common',
                'requirements' => ['goals_created' => 1],
                'sort_order' => 1
            ],
            [
                'key' => 'goal_achiever',
                'name' => 'Goal Achiever',
                'description' => 'Complete your first goal',
                'icon' => '🏆',
                'points' => 25,
                'category' => 'goals',
                'rarity' => 'common',
                'requirements' => ['goals_completed' => 1],
                'sort_order' => 2
            ],
            [
                'key' => 'milestone_master',
                'name' => 'Milestone Master',
                'description' => 'Complete 5 goals',
                'icon' => '🎖️',
                'points' => 50,
                'category' => 'goals',
                'rarity' => 'rare',
                'requirements' => ['goals_completed' => 5],
                'sort_order' => 3
            ],
            [
                'key' => 'goal_champion',
                'name' => 'Goal Champion',
                'description' => 'Complete 10 goals',
                'icon' => '👑',
                'points' => 100,
                'category' => 'goals',
                'rarity' => 'epic',
                'requirements' => ['goals_completed' => 10],
                'sort_order' => 4
            ],
            [
                'key' => 'legendary_achiever',
                'name' => 'Legendary Achiever',
                'description' => 'Complete 25 goals',
                'icon' => '💎',
                'points' => 250,
                'category' => 'goals',
                'rarity' => 'legendary',
                'requirements' => ['goals_completed' => 25],
                'sort_order' => 5
            ],

            // Streak-based achievements
            [
                'key' => 'consistency_starter',
                'name' => 'Consistency Starter',
                'description' => 'Maintain a 3-day streak',
                'icon' => '🔥',
                'points' => 30,
                'category' => 'streak',
                'rarity' => 'common',
                'requirements' => ['current_streak' => 3],
                'sort_order' => 10
            ],
            [
                'key' => 'week_warrior',
                'name' => 'Week Warrior',
                'description' => 'Maintain a 7-day streak',
                'icon' => '⚡',
                'points' => 70,
                'category' => 'streak',
                'rarity' => 'rare',
                'requirements' => ['current_streak' => 7],
                'sort_order' => 11
            ],
            [
                'key' => 'streak_master',
                'name' => 'Streak Master',
                'description' => 'Maintain a 14-day streak',
                'icon' => '🌟',
                'points' => 150,
                'category' => 'streak',
                'rarity' => 'epic',
                'requirements' => ['current_streak' => 14],
                'sort_order' => 12
            ],
            [
                'key' => 'unstoppable_force',
                'name' => 'Unstoppable Force',
                'description' => 'Maintain a 30-day streak',
                'icon' => '🚀',
                'points' => 300,
                'category' => 'streak',
                'rarity' => 'legendary',
                'requirements' => ['current_streak' => 30],
                'sort_order' => 13
            ],

            // Level-based achievements
            [
                'key' => 'level_up',
                'name' => 'Level Up!',
                'description' => 'Reach level 5',
                'icon' => '📈',
                'points' => 50,
                'category' => 'progress',
                'rarity' => 'common',
                'requirements' => ['level' => 5],
                'sort_order' => 20
            ],
            [
                'key' => 'rising_star',
                'name' => 'Rising Star',
                'description' => 'Reach level 10',
                'icon' => '⭐',
                'points' => 100,
                'category' => 'progress',
                'rarity' => 'rare',
                'requirements' => ['level' => 10],
                'sort_order' => 21
            ],
            [
                'key' => 'elite_performer',
                'name' => 'Elite Performer',
                'description' => 'Reach level 20',
                'icon' => '🎗️',
                'points' => 200,
                'category' => 'progress',
                'rarity' => 'epic',
                'requirements' => ['level' => 20],
                'sort_order' => 22
            ],

            // Points-based achievements
            [
                'key' => 'point_collector',
                'name' => 'Point Collector',
                'description' => 'Earn 500 points',
                'icon' => '💰',
                'points' => 50,
                'category' => 'progress',
                'rarity' => 'common',
                'requirements' => ['total_points' => 500],
                'sort_order' => 30
            ],
            [
                'key' => 'point_master',
                'name' => 'Point Master',
                'description' => 'Earn 1000 points',
                'icon' => '💎',
                'points' => 100,
                'category' => 'progress',
                'rarity' => 'rare',
                'requirements' => ['total_points' => 1000],
                'sort_order' => 31
            ],

            // Activity-based achievements
            [
                'key' => 'weekly_warrior',
                'name' => 'Weekly Warrior',
                'description' => 'Complete 5 goals in a week',
                'icon' => '📅',
                'points' => 75,
                'category' => 'milestone',
                'rarity' => 'rare',
                'requirements' => ['weekly_goals_completed' => 5],
                'sort_order' => 40
            ],
            [
                'key' => 'monthly_champion',
                'name' => 'Monthly Champion',
                'description' => 'Complete 20 goals in a month',
                'icon' => '📈',
                'points' => 200,
                'category' => 'milestone',
                'rarity' => 'epic',
                'requirements' => ['monthly_goals_completed' => 20],
                'sort_order' => 41
            ],

            // Special achievements
            [
                'key' => 'goal_creator',
                'name' => 'Goal Creator',
                'description' => 'Create 10 goals',
                'icon' => '🎨',
                'points' => 50,
                'category' => 'special',
                'rarity' => 'common',
                'requirements' => ['goals_created' => 10],
                'sort_order' => 50
            ],
            [
                'key' => 'visionary',
                'name' => 'Visionary',
                'description' => 'Create 25 goals',
                'icon' => '🔮',
                'points' => 125,
                'category' => 'special',
                'rarity' => 'rare',
                'requirements' => ['goals_created' => 25],
                'sort_order' => 51
            ]
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(
                ['key' => $achievement['key']],
                $achievement
            );
        }

        $this->command->info('Achievement seeder completed! Created ' . count($achievements) . ' achievements.');
    }
}