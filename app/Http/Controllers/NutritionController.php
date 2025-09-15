<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NutritionService;
use App\Services\RegimeRecommendationService;
use App\Models\UserDiet;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AddMealEntryRequest;
use App\Http\Requests\UpdateMealEntryRequest;
use App\Http\Requests\SetNutritionGoalsRequest;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Models\MealEntry;

class NutritionController extends BaseController
{
    protected NutritionService $nutritionService;
    protected RegimeRecommendationService $regimeRecommendationService;

    public function __construct(
        NutritionService $nutritionService,
        RegimeRecommendationService $regimeRecommendationService
    ) {
        $this->nutritionService = $nutritionService;
        $this->regimeRecommendationService = $regimeRecommendationService;
    }

    /**
     * Get daily nutrition summary including meals and goals.
     */
    public function getDailySummary(Request $request, string $date = null): JsonResponse
    {
        return $this->execute(function () use ($request, $date) {
            $user = $request->user();
            $targetDate = $date ?? Carbon::now()->toDateString();
            $summary = $this->nutritionService->getDailyNutrition($user, $targetDate);
            return $this->successResponse($summary, 'Daily nutrition summary fetched successfully.');
        });
    }

    /**
     * Get food database for search functionality.
     */
    public function getFoodDatabase(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $query = $request->input('query', '');
            $filters = $request->only(['maxCalories', 'minProtein', 'category', 'restrictions']);
            $foods = $this->nutritionService->searchFoods($query, $filters);
            return $this->successResponse($foods, 'Food database fetched successfully.');
        });
    }

    /**
     * Get available food categories.
     */
    public function getFoodCategories(): JsonResponse
    {
        return $this->execute(function () {
            $categories = $this->nutritionService->getFoodCategories();
            return $this->successResponse($categories, 'Food categories fetched successfully.');
        });
    }

    /**
     * Update water intake.
     */
    public function updateWaterIntake(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $request->validate([
                'amount' => 'required|numeric|min:0',
                'date' => 'nullable|date',
            ]);
            $user = $request->user();
            $result = $this->nutritionService->updateWaterIntake($user, $request->only(['amount', 'date']));
            return $this->successResponse($result, 'Water intake updated successfully.');
        });
    }

    // ===================================================================
    // MEAL ENTRIES MANAGEMENT
    // ===================================================================
    
    /**
     * Get meal entries for a specific date.
     */
    public function getMealEntries(Request $request, string $date): JsonResponse
    {
        return $this->execute(function () use ($request, $date) {
            $user = $request->user();
            $mealEntries = $this->nutritionService->getMealEntries($user, $date);
            return $this->successResponse($mealEntries, 'Meal entries fetched successfully.');
        });
    }
    
    /**
     * Add a new meal entry.
     */
    public function addMealEntry(AddMealEntryRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $user = $request->user();
            $mealEntry = $this->nutritionService->addMealEntry($user, $request->validated());
            return $this->successResponse($mealEntry, 'Meal entry added successfully.', 201);
        });
    }
    
    /**
     * Update an existing meal entry.
     */
    public function updateMealEntry(UpdateMealEntryRequest $request, MealEntry $mealEntry): JsonResponse
    {
        return $this->execute(function () use ($request, $mealEntry) {
            $user = $request->user();
            $updatedMealEntry = $this->nutritionService->updateMealEntry($user, $mealEntry, $request->validated());
            return $this->successResponse($updatedMealEntry, 'Meal entry updated successfully.');
        });
    }
    
    /**
     * Delete a meal entry.
     */
    public function deleteMealEntry(Request $request, MealEntry $mealEntry): JsonResponse
    {
        return $this->execute(function () use ($request, $mealEntry) {
            $user = $request->user();
            $this->nutritionService->deleteMealEntry($user, $mealEntry);
            return $this->successResponse(null, 'Meal entry deleted successfully.', 204);
        });
    }
    
    // ===================================================================
    // NUTRITION GOALS MANAGEMENT
    // ===================================================================
    
    /**
     * Get user's nutrition goals.
     */
    public function getNutritionGoals(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $user = $request->user();
            $goals = $this->nutritionService->getNutritionGoals($user);
            return $this->successResponse($goals, 'Nutrition goals fetched successfully.');
        });
    }
    
    /**
     * Set or update user's nutrition goals.
     */
    public function setNutritionGoals(SetNutritionGoalsRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $user = $request->user();
            $goals = $this->nutritionService->setNutritionGoals($user, $request->validated());
            return $this->successResponse($goals, 'Nutrition goals set successfully.');
        });
    }
    
    /**
     * Generate personalized diet recommendations and smart regime recommendations
     */
    public function generatePersonalizedDiet(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            $preferences = $request->validate([
                'goals' => 'array',
                'restrictions' => 'array',
                'activity_level' => 'string',
                'target_calories' => 'integer|min:1200|max:5000',
                'preferred_difficulty' => 'sometimes|string|in:easy,moderate,hard',
                'time_availability' => 'sometimes|string|in:low,medium,high',
                'budget_preference' => 'sometimes|string|in:low,medium,high',
                'cooking_experience' => 'sometimes|string|in:beginner,intermediate,advanced',
                'meal_prep_preference' => 'sometimes|boolean',
                'social_eating_frequency' => 'sometimes|string|in:low,medium,high',
                'travel_frequency' => 'sometimes|string|in:low,medium,high'
            ]);
            
            Log::info('Generating personalized diet and smart regime recommendations for user: ' . $user->id, $preferences);
            
            $diet = $this->regimeRecommendationService->generatePersonalizedDiet($user, $preferences);
            
            return $this->successResponse($diet, 'Personalized diet and smart regime recommendations generated successfully');
        }, 'Generate personalized diet and smart regime recommendations');
    }
    
    /**
     * Start following a specific regime
     */
    public function startRegime(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            $validated = $request->validate([
                'regime_id' => 'required|string',
                'regime_name' => 'required|string|max:255',
                'regime_type' => 'required|string|in:professional,custom,ai_generated',
                'regime_config' => 'required|array',
                'target_duration_days' => 'sometimes|integer|min:1|max:365',
                'start_date' => 'sometimes|date',
                'personal_notes' => 'sometimes|string|max:1000'
            ]);
            
            // Check if user already has an active regime
            $activeRegime = UserDiet::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
                
            if ($activeRegime) {
                return $this->errorResponse(
                    'Vous avez déjà un régime actif. Terminez-le ou mettez-le en pause avant d\'en commencer un nouveau.',
                    400
                );
            }
            
            $userDiet = UserDiet::create([
                'user_id' => $user->id,
                'diet_id' => $validated['regime_id'],
                'diet_name' => $validated['regime_name'],
                'diet_type' => $validated['regime_type'],
                'diet_config' => $validated['regime_config'],
                'start_date' => $validated['start_date'] ?? now(),
                'target_duration_days' => $validated['target_duration_days'] ?? 30,
                'status' => 'active',
                'notes' => $validated['personal_notes'] ?? null
            ]);
            
            // Award starting bonus XP
            $userDiet->addXP(100, 'regime_started');
            
            Log::info('User started new regime', [
                'user_id' => $user->id,
                'regime_id' => $validated['regime_id'],
                'regime_type' => $validated['regime_type']
            ]);
            
            return $this->successResponse($userDiet, 'Régime démarré avec succès');
        }, 'Start regime');
    }
    
    /**
     * Update daily regime adherence score
     */
    public function updateRegimeScore(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            $validated = $request->validate([
                'score' => 'required|numeric|min:0|max:100',
                'date' => 'sometimes|date',
                'notes' => 'sometimes|string|max:500'
            ]);
            
            $activeRegime = UserDiet::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
                
            if (!$activeRegime) {
                return $this->errorResponse('Aucun régime actif trouvé', 404);
            }
            
            $date = $validated['date'] ?? now()->toDateString();
            $score = $validated['score'];
            
            $activeRegime->recordDailyScore($score, $date);
            
            // Award XP based on score
            $xpAwarded = max(5, round($score / 10));
            $activeRegime->addXP($xpAwarded, 'daily_adherence');
            
            if (isset($validated['notes'])) {
                $activeRegime->notes = ($activeRegime->notes ?? '') . 
                    "\n[$date] Score: $score - " . $validated['notes'];
                $activeRegime->save();
            }
            
            Log::info('Regime score updated', [
                'user_id' => $user->id,
                'regime_id' => $activeRegime->id,
                'score' => $score,
                'date' => $date
            ]);
            
            return $this->successResponse([
                'score_recorded' => $score,
                'date' => $date,
                'xp_awarded' => $xpAwarded,
                'current_streak' => $activeRegime->streak_days,
                'total_xp' => $activeRegime->total_xp_earned
            ], 'Score d\'adhérence enregistré');
        }, 'Update regime score');
    }
    
    /**
     * Get user's current regime status and progress
     */
    public function getCurrentRegime()
    {
        return $this->execute(function () {
            $user = $this->getAuthenticatedUser();
            
            $currentRegime = UserDiet::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
                
            if (!$currentRegime) {
                return $this->successResponse(null, 'Aucun régime actif');
            }
            
            $progressStats = $currentRegime->getProgressStats();
            
            return $this->successResponse([
                'regime' => $currentRegime,
                'progress_stats' => $progressStats,
                'can_record_today' => $this->canRecordScoreToday($currentRegime),
                'next_milestones' => $this->getNextMilestones($currentRegime)
            ], 'Régime actuel récupéré');
        }, 'Get current regime');
    }
    
    /**
     * Pause current regime
     */
    public function pauseRegime(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            $validated = $request->validate([
                'reason' => 'sometimes|string|max:500'
            ]);
            
            $activeRegime = UserDiet::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
                
            if (!$activeRegime) {
                return $this->errorResponse('Aucun régime actif trouvé', 404);
            }
            
            $activeRegime->pause($validated['reason'] ?? '');
            
            Log::info('Regime paused', [
                'user_id' => $user->id,
                'regime_id' => $activeRegime->id,
                'reason' => $validated['reason'] ?? 'Non spécifié'
            ]);
            
            return $this->successResponse($activeRegime, 'Régime mis en pause');
        }, 'Pause regime');
    }
    
    /**
     * Resume paused regime
     */
    public function resumeRegime()
    {
        return $this->execute(function () {
            $user = $this->getAuthenticatedUser();
            
            $pausedRegime = UserDiet::where('user_id', $user->id)
                ->where('status', 'paused')
                ->orderBy('updated_at', 'desc')
                ->first();
                
            if (!$pausedRegime) {
                return $this->errorResponse('Aucun régime en pause trouvé', 404);
            }
            
            $pausedRegime->resume();
            
            Log::info('Regime resumed', [
                'user_id' => $user->id,
                'regime_id' => $pausedRegime->id
            ]);
            
            return $this->successResponse($pausedRegime, 'Régime repris');
        }, 'Resume regime');
    }
    
    /**
     * Complete current regime
     */
    public function completeRegime(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            $validated = $request->validate([
                'completion_notes' => 'sometimes|string|max:1000',
                'rating' => 'sometimes|integer|min:1|max:5',
                'would_recommend' => 'sometimes|boolean'
            ]);
            
            $activeRegime = UserDiet::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
                
            if (!$activeRegime) {
                return $this->errorResponse('Aucun régime actif trouvé', 404);
            }
            
            $activeRegime->complete();
            
            // Add completion notes if provided
            if (isset($validated['completion_notes'])) {
                $activeRegime->notes = ($activeRegime->notes ?? '') . 
                    "\n[COMPLETED] " . $validated['completion_notes'];
            }
            
            if (isset($validated['rating'])) {
                $activeRegime->notes = ($activeRegime->notes ?? '') . 
                    "\n[RATING] " . $validated['rating'] . '/5';
            }
            
            $activeRegime->save();
            
            Log::info('Regime completed', [
                'user_id' => $user->id,
                'regime_id' => $activeRegime->id,
                'days_completed' => $activeRegime->days_active,
                'final_score' => $activeRegime->current_score
            ]);
            
            return $this->successResponse([
                'regime' => $activeRegime,
                'completion_stats' => [
                    'days_completed' => $activeRegime->days_active,
                    'final_score' => $activeRegime->current_score,
                    'total_xp_earned' => $activeRegime->total_xp_earned,
                    'achievements_unlocked' => count($activeRegime->achievements_unlocked ?? [])
                ]
            ], 'Régime terminé avec succès');
        }, 'Complete regime');
    }
    
    /**
     * Get regime history for user
     */
    public function getRegimeHistory(Request $request)
    {
        return $this->execute(function () use ($request) {
            $user = $this->getAuthenticatedUser();
            
            $limit = $request->query('limit', 10);
            $status = $request->query('status', null);
            
            $query = UserDiet::where('user_id', $user->id)
                ->orderBy('start_date', 'desc');
                
            if ($status) {
                $query->where('status', $status);
            }
            
            $regimes = $query->limit($limit)->get();
            
            $summary = [
                'total_regimes' => UserDiet::where('user_id', $user->id)->count(),
                'completed_regimes' => UserDiet::where('user_id', $user->id)->where('status', 'completed')->count(),
                'total_days_tracked' => UserDiet::where('user_id', $user->id)->sum('days_active'),
                'total_xp_earned' => UserDiet::where('user_id', $user->id)->sum('total_xp_earned'),
                'best_streak' => UserDiet::where('user_id', $user->id)->max('streak_days'),
                'average_score' => round(UserDiet::where('user_id', $user->id)->avg('current_score'), 2)
            ];
            
            return $this->successResponse([
                'regimes' => $regimes,
                'summary' => $summary
            ], 'Historique des régimes récupéré');
        }, 'Get regime history');
    }
    
    /**
     * Helper method to check if user can record score today
     */
    private function canRecordScoreToday(UserDiet $regime): bool
    {
        $today = now()->toDateString();
        $dailyScores = $regime->daily_scores ?? [];
        
        return !isset($dailyScores[$today]);
    }
    
    /**
     * Helper method to get next milestones
     */
    private function getNextMilestones(UserDiet $regime): array
    {
        $milestones = [];
        
        // Days milestone
        $nextDayMilestone = null;
        $dayMilestones = [7, 14, 21, 30, 60, 90];
        foreach ($dayMilestones as $milestone) {
            if ($regime->days_active < $milestone) {
                $nextDayMilestone = [
                    'type' => 'days',
                    'target' => $milestone,
                    'current' => $regime->days_active,
                    'remaining' => $milestone - $regime->days_active
                ];
                break;
            }
        }
        
        // Streak milestone
        $nextStreakMilestone = null;
        $streakMilestones = [7, 14, 21, 30];
        foreach ($streakMilestones as $milestone) {
            if ($regime->streak_days < $milestone) {
                $nextStreakMilestone = [
                    'type' => 'streak',
                    'target' => $milestone,
                    'current' => $regime->streak_days,
                    'remaining' => $milestone - $regime->streak_days
                ];
                break;
            }
        }
        
        // XP milestone
        $nextXPMilestone = null;
        $xpMilestones = [500, 1000, 2500, 5000, 10000];
        foreach ($xpMilestones as $milestone) {
            if ($regime->total_xp_earned < $milestone) {
                $nextXPMilestone = [
                    'type' => 'xp',
                    'target' => $milestone,
                    'current' => $regime->total_xp_earned,
                    'remaining' => $milestone - $regime->total_xp_earned
                ];
                break;
            }
        }
        
        return array_filter([
            'days' => $nextDayMilestone,
            'streak' => $nextStreakMilestone,
            'xp' => $nextXPMilestone
        ]);
    }

    public function getProfessionalRegimes(): JsonResponse
    {
        return $this->execute(function () {
            $professionalRegimes = $this->regimeRecommendationService->getProfessionalRegimes();
            return $this->successResponse($professionalRegimes, 'Professional regimes fetched successfully.');
        });
    }

    public function getMealTemplates(): JsonResponse
    {
        return $this->execute(function () {
            $mealTemplates = $this->nutritionService->getMealTemplates();
            return $this->successResponse($mealTemplates, 'Meal templates fetched successfully.');
        });
    }
}