<?php
// app/Services/CalorieCalculatorService.php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class CalorieCalculatorService
{
    /**
     * MET values for different exercise types
     * Based on the Compendium of Physical Activities
     */
    private const MET_VALUES = [
        // Strength Training
        'general' => 6.0,
        'strength' => 6.0,
        'weight_lifting' => 6.0,
        'bodyweight' => 5.0,
        'resistance' => 5.5,
        
        // Cardiovascular
        'cardio' => 8.0,
        'running' => 12.0,
        'cycling' => 8.5,
        'swimming' => 11.0,
        'walking' => 4.0,
        'jogging' => 7.0,
        
        // High Intensity
        'hiit' => 10.0,
        'circuit' => 8.0,
        'crossfit' => 9.0,
        'tabata' => 12.0,
        'interval' => 9.5,
        
        // Flexibility & Recovery
        'flexibility' => 3.0,
        'yoga' => 3.0,
        'pilates' => 4.0,
        'stretching' => 2.5,
        'mobility' => 3.0,
        
        // Body Parts (mapped to strength)
        'chest' => 6.0,
        'back' => 6.0,
        'legs' => 8.0,
        'shoulders' => 5.0,
        'arms' => 5.0,
        'abs' => 4.0,
        'core' => 5.0,
        'glutes' => 6.0,
        'biceps' => 5.0,
        'triceps' => 5.0,
        'quads' => 8.0,
        'hamstrings' => 8.0,
        'calves' => 6.0,
        
        // Intensity levels
        'light' => 4.0,
        'moderate' => 6.0,
        'vigorous' => 8.0,
        'very_vigorous' => 12.0,
    ];

    /**
     * Difficulty multipliers
     */
    private const DIFFICULTY_MULTIPLIERS = [
        'beginner' => 0.8,
        'intermediate' => 1.0,
        'advanced' => 1.3,
    ];

    /**
     * Calculate calories burned using MET formula
     * Formula: Calories = MET × weight(kg) × time(hours)
     *
     * @param int $durationMinutes
     * @param float $weightKg
     * @param string $exerciseType
     * @param string $difficulty
     * @return int
     */
    public function calculate(
        int $durationMinutes, 
        float $weightKg, 
        string $exerciseType = 'general',
        string $difficulty = 'intermediate'
    ): int {
        try {
            // Validate inputs
            if ($durationMinutes <= 0 || $weightKg <= 0) {
                Log::warning('Invalid input for calorie calculation', [
                    'duration' => $durationMinutes,
                    'weight' => $weightKg
                ]);
                return max(1, round($durationMinutes * 5)); // Fallback: ~5 cal/min
            }

            // Get MET value for exercise type
            $metValue = $this->getMETValue($exerciseType);
            
            // Apply difficulty multiplier
            $difficultyMultiplier = $this->getDifficultyMultiplier($difficulty);
            $adjustedMET = $metValue * $difficultyMultiplier;
            
            // Convert minutes to hours
            $durationHours = $durationMinutes / 60;
            
            // Calculate calories: MET × weight(kg) × time(hours)
            $calories = $adjustedMET * $weightKg * $durationHours;
            
            // Apply additional factors based on exercise type
            $calories = $this->applyAdditionalFactors($calories, $exerciseType, $durationMinutes);
            
            // Round to nearest integer and ensure minimum
            $finalCalories = max(1, round($calories));
            
            Log::debug('Calorie calculation completed', [
                'duration_minutes' => $durationMinutes,
                'weight_kg' => $weightKg,
                'exercise_type' => $exerciseType,
                'difficulty' => $difficulty,
                'met_value' => $metValue,
                'difficulty_multiplier' => $difficultyMultiplier,
                'final_calories' => $finalCalories
            ]);
            
            return $finalCalories;
            
        } catch (\Exception $e) {
            Log::error('Error calculating calories', [
                'duration' => $durationMinutes,
                'weight' => $weightKg,
                'exercise_type' => $exerciseType,
                'difficulty' => $difficulty,
                'error' => $e->getMessage()
            ]);
            
            // Return estimated calories as fallback
            return max(1, round($durationMinutes * 5)); // ~5 calories per minute fallback
        }
    }

    /**
     * Calculate calories for a complete workout with multiple exercises
     *
     * @param array $exercises
     * @param float $weightKg
     * @param int $totalDurationMinutes
     * @param string $defaultDifficulty
     * @return int
     */
    public function calculateForWorkout(
        array $exercises, 
        float $weightKg, 
        int $totalDurationMinutes,
        string $defaultDifficulty = 'intermediate'
    ): int {
        try {
            if (empty($exercises)) {
                return $this->calculate($totalDurationMinutes, $weightKg, 'general', $defaultDifficulty);
            }

            $totalCalories = 0;
            $totalExerciseTime = 0;

            foreach ($exercises as $exercise) {
                $exerciseTime = $this->calculateExerciseTime($exercise);
                $totalExerciseTime += $exerciseTime;
                
                $exerciseType = $exercise['body_part'] ?? $exercise['category'] ?? 'general';
                $difficulty = $exercise['difficulty'] ?? $defaultDifficulty;
                
                $calories = $this->calculate($exerciseTime, $weightKg, $exerciseType, $difficulty);
                $totalCalories += $calories;
            }

            // If total exercise time is less than total duration, add calories for remaining time
            if ($totalExerciseTime < $totalDurationMinutes) {
                $remainingTime = $totalDurationMinutes - $totalExerciseTime;
                $totalCalories += $this->calculate($remainingTime, $weightKg, 'general', $defaultDifficulty);
            }

            return round($totalCalories);

        } catch (\Exception $e) {
            Log::error('Error calculating calories for workout', [
                'exercises_count' => count($exercises),
                'weight' => $weightKg,
                'duration' => $totalDurationMinutes,
                'error' => $e->getMessage()
            ]);
            
            // Fallback calculation
            return $this->calculate($totalDurationMinutes, $weightKg, 'general', $defaultDifficulty);
        }
    }

    /**
     * Calculate estimated time for a single exercise
     *
     * @param array $exercise
     * @return int Duration in minutes
     */
    private function calculateExerciseTime(array $exercise): int
    {
        try {
            $sets = $exercise['sets'] ?? 1;
            
            if (isset($exercise['duration_seconds'])) {
                // Time-based exercise
                $timePerSet = $exercise['duration_seconds'] / 60; // Convert to minutes
            } elseif (isset($exercise['duration'])) {
                // Legacy support for 'duration' field
                $timePerSet = $exercise['duration'] / 60; // Convert to minutes
            } else {
                // Rep-based exercise - estimate 3 seconds per rep
                $reps = $exercise['reps'] ?? 10;
                $timePerSet = ($reps * 3) / 60; // Convert to minutes
            }

            $totalTime = $timePerSet * $sets;
            
            // Add rest time between sets
            $restTime = $exercise['rest_time_seconds'] ?? $exercise['restTime'] ?? $exercise['rest_time'] ?? 60;
            if ($sets > 1) {
                $totalTime += (($sets - 1) * $restTime) / 60; // Convert to minutes
            }

            return max(1, round($totalTime)); // Minimum 1 minute

        } catch (\Exception $e) {
            Log::warning('Error calculating exercise time', [
                'exercise' => $exercise,
                'error' => $e->getMessage()
            ]);
            
            return 2; // Default 2 minutes
        }
    }

    /**
     * Get MET value for specific exercise type
     *
     * @param string $exerciseType
     * @return float
     */
    public function getMETValue(string $exerciseType): float
    {
        // Normalize exercise type
        $exerciseType = strtolower(trim($exerciseType));
        
        // Direct match
        if (isset(self::MET_VALUES[$exerciseType])) {
            return self::MET_VALUES[$exerciseType];
        }
        
        // Partial matches for flexibility
        foreach (self::MET_VALUES as $key => $value) {
            if (strpos($exerciseType, $key) !== false || strpos($key, $exerciseType) !== false) {
                return $value;
            }
        }
        
        // Default fallback
        return self::MET_VALUES['general'];
    }

    /**
     * Get difficulty multiplier
     *
     * @param string $difficulty
     * @return float
     */
    private function getDifficultyMultiplier(string $difficulty): float
    {
        $difficulty = strtolower(trim($difficulty));
        
        return self::DIFFICULTY_MULTIPLIERS[$difficulty] ?? 
               self::DIFFICULTY_MULTIPLIERS['intermediate'];
    }

    /**
     * Apply additional factors based on exercise type
     *
     * @param float $baseCalories
     * @param string $exerciseType
     * @param int $durationMinutes
     * @return float
     */
    private function applyAdditionalFactors(
        float $baseCalories, 
        string $exerciseType, 
        int $durationMinutes
    ): float {
        $exerciseType = strtolower($exerciseType);
        
        // HIIT gets a slight boost due to afterburn effect (EPOC)
        if (in_array($exerciseType, ['hiit', 'interval', 'tabata', 'circuit'])) {
            $baseCalories *= 1.1; // 10% bonus for afterburn
        }
        
        // Very short workouts get slightly reduced efficiency
        if ($durationMinutes < 10) {
            $baseCalories *= 0.95;
        }
        
        // Very long workouts account for fatigue
        if ($durationMinutes > 90) {
            $baseCalories *= 0.95;
        }
        
        return $baseCalories;
    }

    /**
     * Calculate calories burned per minute for given weight and exercise type
     *
     * @param float $weightKg
     * @param string $exerciseType
     * @param string $difficulty
     * @return float
     */
    public function getCaloriesPerMinute(
        float $weightKg, 
        string $exerciseType = 'general',
        string $difficulty = 'intermediate'
    ): float {
        $metValue = $this->getMETValue($exerciseType);
        $difficultyMultiplier = $this->getDifficultyMultiplier($difficulty);
        $adjustedMET = $metValue * $difficultyMultiplier;
        
        // Convert to calories per minute: (MET × weight × 1 hour) / 60 minutes
        return ($adjustedMET * $weightKg) / 60;
    }

    /**
     * Estimate workout intensity based on calories per minute
     *
     * @param int $totalCalories
     * @param int $durationMinutes
     * @return string
     */
    public function calculateIntensity(int $totalCalories, int $durationMinutes): string
    {
        if ($durationMinutes === 0) {
            return 'unknown';
        }

        $caloriesPerMinute = $totalCalories / $durationMinutes;

        if ($caloriesPerMinute >= 12) {
            return 'high';
        } elseif ($caloriesPerMinute >= 8) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get all available exercise types with their MET values
     *
     * @return array
     */
    public function getExerciseTypes(): array
    {
        return self::MET_VALUES;
    }

    /**
     * Get calorie estimates for different intensities
     *
     * @param int $durationMinutes
     * @param float $weightKg
     * @return array
     */
    public function getCalorieEstimates(int $durationMinutes, float $weightKg): array
    {
        $estimates = [];
        
        foreach (['light', 'moderate', 'vigorous', 'very_vigorous'] as $intensity) {
            $estimates[$intensity] = [
                'intensity' => ucfirst($intensity),
                'calories' => $this->calculate($durationMinutes, $weightKg, $intensity),
                'calories_per_minute' => $this->getCaloriesPerMinute($weightKg, $intensity)
            ];
        }
        
        return $estimates;
    }

    /**
     * Calculate target calories for fitness goals
     *
     * @param string $goal
     * @param float $weightKg
     * @param int $sessionsPerWeek
     * @return array
     */
    public function calculateTargetCalories(
        string $goal, 
        float $weightKg, 
        int $sessionsPerWeek = 3
    ): array {
        $targets = [
            'weight_loss' => [
                'weekly_calories' => $weightKg * 35, // ~35 cal/kg/week for weight loss
                'description' => 'Perte de poids progressive'
            ],
            'maintenance' => [
                'weekly_calories' => $weightKg * 20, // ~20 cal/kg/week for maintenance
                'description' => 'Maintien de la forme physique'
            ],
            'muscle_gain' => [
                'weekly_calories' => $weightKg * 25, // ~25 cal/kg/week for muscle gain
                'description' => 'Prise de masse musculaire'
            ]
        ];
        
        if (isset($targets[$goal])) {
            $weeklyTarget = $targets[$goal]['weekly_calories'];
            $targets[$goal]['per_session'] = round($weeklyTarget / $sessionsPerWeek);
            $targets[$goal]['sessions_per_week'] = $sessionsPerWeek;
            
            return $targets[$goal];
        }
        
        // Default maintenance calories
        return [
            'weekly_calories' => $weightKg * 20,
            'per_session' => round(($weightKg * 20) / $sessionsPerWeek),
            'sessions_per_week' => $sessionsPerWeek,
            'description' => 'Maintien de la forme physique'
        ];
    }

    /**
     * Estimate calories for workout session based on categories
     *
     * @param int $totalDurationMinutes
     * @param array $exerciseCategories
     * @param float $weightKg
     * @param string $difficulty
     * @return array
     */
    public function estimateSessionCalories(
        int $totalDurationMinutes,
        array $exerciseCategories,
        float $weightKg,
        string $difficulty = 'intermediate'
    ): array {
        $breakdown = [];
        $totalCalories = 0;
        
        if (empty($exerciseCategories)) {
            $exerciseCategories = ['general'];
        }
        
        // Calculate per category
        if (count($exerciseCategories) > 1) {
            $durationPerCategory = $totalDurationMinutes / count($exerciseCategories);
            
            foreach ($exerciseCategories as $category) {
                $categoryCalories = $this->calculate(
                    $durationPerCategory, 
                    $weightKg, 
                    $category, 
                    $difficulty
                );
                
                $breakdown[$category] = [
                    'duration_minutes' => $durationPerCategory,
                    'calories' => $categoryCalories,
                    'calories_per_minute' => round($categoryCalories / $durationPerCategory, 1)
                ];
                
                $totalCalories += $categoryCalories;
            }
        } else {
            // Single category
            $category = $exerciseCategories[0];
            $totalCalories = $this->calculate($totalDurationMinutes, $weightKg, $category, $difficulty);
            
            $breakdown[$category] = [
                'duration_minutes' => $totalDurationMinutes,
                'calories' => $totalCalories,
                'calories_per_minute' => round($totalCalories / $totalDurationMinutes, 1)
            ];
        }
        
        return [
            'total_calories' => $totalCalories,
            'total_duration' => $totalDurationMinutes,
            'average_calories_per_minute' => round($totalCalories / $totalDurationMinutes, 1),
            'breakdown' => $breakdown,
            'intensity' => $this->calculateIntensity($totalCalories, $totalDurationMinutes)
        ];
    }
}