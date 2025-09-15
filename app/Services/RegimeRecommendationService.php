<?php

namespace App\Services;

use App\Models\User;
use App\Models\NutritionGoal;
use App\Models\MealEntry;
use App\Services\NutritionService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RegimeRecommendationService
{
    private NutritionService $nutritionService;
    
    public function __construct(NutritionService $nutritionService)
    {
        $this->nutritionService = $nutritionService;
    }

    /**
     * Generate personalized diet for a user
     */
    public function generatePersonalizedDiet(User $user, array $preferences): array
    {
        // Get user's current goals
        $goals = $this->nutritionService->getNutritionGoals($user);
        
        // Analyze user profile
        $userProfile = $this->analyzeUserProfile($user);

        // Calculate personalized recommendations based on preferences
        $targetCalories = $preferences['target_calories'] ?? $goals['calories'];
        $activityLevel = $preferences['activity_level'] ?? 'moderate';
        $dietGoals = $preferences['goals'] ?? ['balanced'];
        $restrictions = $preferences['restrictions'] ?? [];
        
        // Adjust calories based on activity level
        $calorieAdjustments = [
            'sedentary' => 0.9,
            'light' => 1.0,
            'moderate' => 1.1,
            'active' => 1.2,
            'very_active' => 1.3
        ];
        
        $adjustedCalories = $targetCalories * ($calorieAdjustments[$activityLevel] ?? 1.0);
        
        // Generate macro distribution based on goals
        $macroDistribution = $this->calculateMacroDistribution($dietGoals);
        
        // Create recommended foods list based on restrictions
        $recommendedFoods = $this->getRecommendedFoods($restrictions, $macroDistribution);
        
        // Calculate adherence probability
        $adherenceProbability = $this->calculateAdherenceProbability($user, $restrictions, $dietGoals);
        
        // Generate smart recommendations - replacing the call to a non-existent method
        $smartRecommendations = $this->generateInitialRegimeRecommendation($userProfile, $preferences);
        
        return [
            'target_calories' => round($adjustedCalories),
            'macro_distribution' => $macroDistribution,
            'recommended_foods' => $recommendedFoods,
            'meal_suggestions' => $this->generateMealSuggestions($adjustedCalories, $macroDistribution, $restrictions),


            'tips' => $this->generateNutritionTips($dietGoals, $restrictions, $userProfile, $adherenceProbability, $this->assessDietDifficulty($restrictions, $dietGoals, $user)),
            'adherence_probability' => $adherenceProbability,
            'smart_recommendations' => $smartRecommendations, // Now uses the new method
            'personalization_score' => $this->calculatePersonalizationScore($user, $preferences, $userProfile, $macroDistribution, $this->assessDietDifficulty($restrictions, $dietGoals, $user)),
            'difficulty_assessment' => $this->assessDietDifficulty($restrictions, $dietGoals, $user),
            'success_factors' => $this->identifySuccessFactors($user, $preferences),
            'potential_challenges' => $this->identifyPotentialChallenges($user, $restrictions),
            'created_at' => now()->toISOString()
        ];
    }
    
    /**
     * Generates an initial regime recommendation based on user profile and preferences.
     * This can be expanded with more sophisticated logic later.
     */
    private function generateInitialRegimeRecommendation(array $userProfile, array $preferences): array
    {
        $selectedRegime = null;
        $availableRegimes = $this->getAvailableRegimes();
        $bestScore = -1;

        foreach ($availableRegimes as $regime) {
            $score = $this->scoreRegimeCompatibility($regime, $userProfile, $preferences);
            if ($score > $bestScore) {
                $bestScore = $score;
                $selectedRegime = $regime;
            }
        }

        if ($selectedRegime) {
            return ['type' => 'recommended', 'details' => $selectedRegime, 'score' => round($bestScore, 2)];
        } else {
            // Fallback if no specific regime is selected
            return ['type' => 'default', 'details' => 'balanced_moderate', 'score' => 0.5];
        }
    }
    
    /**
     * Calculate adherence probability based on user profile and diet characteristics
     */
    private function calculateAdherenceProbability(User $user, array $restrictions, array $goals): array
    {
        $baseScore = 0.7; // 70% base probability
        
        // Adjust based on restrictions complexity
        $restrictionPenalty = count($restrictions) * 0.05;
        $baseScore -= $restrictionPenalty;
        
        // Adjust based on goals complexity
        $goalComplexity = $this->assessGoalComplexity($goals) * 0.1;
        $baseScore -= $goalComplexity;
        
        // User history factor (placeholder - would analyze actual history)
        $historyFactor = $this->getUserHistoryFactor($user);
        $baseScore += $historyFactor;
        
        $probability = max(0.1, min(0.95, $baseScore));
        
        return [
            'probability' => round($probability, 2),
            'confidence' => 'medium',
            'factors' => [
                'base_score' => 0.7,
                'restriction_impact' => -$restrictionPenalty,
                'goal_complexity_impact' => -$goalComplexity,
                'history_bonus' => $historyFactor
            ],
            'recommendations' => $this->getAdherenceRecommendations($probability)
        ];
    }
    
    /**
     * Calculate personalization score
     */
    private function calculatePersonalizationScore(User $user, array $preferences, array $userProfile, array $macroDistribution, array $difficultyAssessment): array
    {
        $score = 0;
        $maxScore = 0;
        
        // Factor 1: Activity level alignment
        // If user specified activity level and it aligns with the macro distribution used, score higher
        if (isset($preferences['activity_level'])) {
            $maxScore += 20;
            // Example: More protein/carbs for active, less for sedentary
            if (
                ($preferences['activity_level'] === 'active' && ($macroDistribution['protein_percentage'] ?? 0) > 30) ||
                ($preferences['activity_level'] === 'sedentary' && ($macroDistribution['carbs_percentage'] ?? 0) < 50)
            ) {
                $score += 20;
            }
        }
        
        // Factor 2: Dietary restrictions alignment
        // If user specified restrictions and the recommended foods respect them (already handled by getRecommendedFoods, so check if any restrictions were specified)
        if (isset($preferences['restrictions']) && !empty($preferences['restrictions'])) {
            $maxScore += 15;
            // Assuming getRecommendedFoods already filtered properly, this just checks if the feature was used.
            // A more advanced check would verify that *all* recommended foods indeed respect the restrictions.
            $score += 15;
        }
        
        // Factor 3: Goals alignment
        // Check if the generated macro distribution aligns with user's stated goals
        if (isset($preferences['goals']) && !empty($preferences['goals'])) {
            $maxScore += 25;
            $goalAlignmentScore = 0;
            foreach ($preferences['goals'] as $goal) {
                switch ($goal) {
                    case 'muscle_gain':
                    case 'high_protein':
                        if (($macroDistribution['protein_percentage'] ?? 0) >= 30) $goalAlignmentScore += 8;
                        break;
                    case 'weight_loss':
                        if (($macroDistribution['fat_percentage'] ?? 0) >= 30 && ($macroDistribution['carbs_percentage'] ?? 0) < 40) $goalAlignmentScore += 8;
                        break;
                    case 'keto':
                        if (($macroDistribution['carbs_percentage'] ?? 0) < 10 && ($macroDistribution['fat_percentage'] ?? 0) > 60) $goalAlignmentScore += 10;
                        break;
                    // Add more goal-specific checks
                }
            }
            $score += min($goalAlignmentScore, 25); // Cap score contribution
        }
        
        // Factor 4: Calorie target alignment (already done by `generatePersonalizedDiet`)
        if (isset($preferences['target_calories'])) {
            $maxScore += 20;
            $score += 20; // Assume perfect alignment if target was provided
        }
        
        // Factor 5: Preferred difficulty alignment
        if (isset($preferences['preferred_difficulty'])) {
            $maxScore += 10;
            $preferredDifficulty = strtolower($preferences['preferred_difficulty']);
            $actualDifficultyLevel = strtolower($difficultyAssessment['level'] ?? 'moderate');
            
            if ($preferredDifficulty === $actualDifficultyLevel) {
                $score += 10;
            } elseif (
                ($preferredDifficulty === 'easy' && $actualDifficultyLevel === 'modéré') ||
                ($preferredDifficulty === 'moderate' && ($actualDifficultyLevel === 'facile' || $actualDifficultyLevel === 'difficile')) ||
                ($preferredDifficulty === 'hard' && $actualDifficultyLevel === 'modéré')
            ) {
                $score += 5; // Partial match
            }
        }
        
        // Factor 6: Lifestyle/Time/Budget alignment (from userProfile, if available)
        $lifestyleAlignmentScore = 0;
        $maxScore += 10; // Max 10 points for these factors

        if (($userProfile['lifestyle_factors']['irregular_schedule'] ?? false) && in_array('flexible_lifestyle', array_column($this->getAvailableRegimes(), 'id'))) {
            // If user has irregular schedule and a flexible regime is available and possibly recommended
            $lifestyleAlignmentScore += 3;
        }
        if (($userProfile['time_availability']['level'] ?? 'medium') === 'low' && ($difficultyAssessment['level'] ?? 'moderate') === 'facile') {
            // If user has low time and recommended difficulty is easy
            $lifestyleAlignmentScore += 3;
        }
        // Add more checks for budget constraints, cooking experience, etc.
        
        $score += min($lifestyleAlignmentScore, 10); // Cap score contribution
        
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
        
        return [
            'score' => $percentage,
            'level' => $this->getPersonalizationLevel($percentage),
            'details' => [
                'activity_based' => isset($preferences['activity_level']),
                'restriction_based' => isset($preferences['restrictions']) && !empty($preferences['restrictions']),
                'goal_based' => isset($preferences['goals']) && !empty($preferences['goals']),
                'calorie_customized' => isset($preferences['target_calories']),
                'difficulty_aligned' => isset($preferences['preferred_difficulty']) && $preferredDifficulty === $actualDifficultyLevel,
                'lifestyle_considered' => $lifestyleAlignmentScore > 0
            ]
        ];
    }
    
    /**
     * Assess diet difficulty
     */
    private function assessDietDifficulty(array $restrictions, array $goals, User $user): array
    {
        $difficulty = 0;
        $factors = [];
        
        // Restrictions add difficulty
        $restrictionDifficulty = count($restrictions) * 10;
        $difficulty += $restrictionDifficulty;
        $factors['restrictions'] = $restrictionDifficulty;
        
        // Complex goals add difficulty
        $goalDifficulty = $this->assessGoalComplexity($goals) * 20;
        $difficulty += $goalDifficulty;
        $factors['goals'] = $goalDifficulty;
        
        // User experience reduces difficulty
        $experienceBonus = $this->getUserExperienceLevel($user) * 15;
        $difficulty -= $experienceBonus;
        $factors['experience_bonus'] = -$experienceBonus;
        
        $difficulty = max(0, min(100, $difficulty));
        
        return [
            'score' => $difficulty,
            'level' => $this->getDifficultyLevel($difficulty),
            'factors' => $factors,
            'recommendations' => $this->getDifficultyRecommendations($difficulty)
        ];
    }
    
    /**
     * Identify success factors
     */
    private function identifySuccessFactors(User $user, array $preferences): array
    {
        return [
            'user_motivation' => 'Utilisateur motivé avec des objectifs clairs',
            'realistic_goals' => 'Objectifs réalisables et mesurables',
            'flexible_approach' => 'Approche flexible permettant les ajustements',
            'progressive_difficulty' => 'Difficulté progressive pour maintenir l\'engagement',
            'clear_guidelines' => 'Instructions claires et faciles à suivre'
        ];
    }
    
    /**
     * Identify potential challenges
     */
    private function identifyPotentialChallenges(User $user, array $restrictions): array
    {
        $challenges = [];
        
        if (count($restrictions) > 2) {
            $challenges[] = [
                'type' => 'complexity',
                'description' => 'Nombreuses restrictions alimentaires',
                'mitigation' => 'Planification de repas et préparation à l\'avance'
            ];
        }
        
        $challenges[] = [
            'type' => 'consistency',
            'description' => 'Maintenir la régularité sur le long terme',
            'mitigation' => 'Suivi quotidien et récompenses pour les étapes'
        ];
        
        $challenges[] = [
            'type' => 'social_pressure',
            'description' => 'Pression sociale lors des sorties',
            'mitigation' => 'Stratégies pour les situations sociales'
        ];
        
        return $challenges;
    }
    
    // Helper methods
    private function assessGoalComplexity(array $goals): float
    {
        $complexGoals = ['keto', 'paleo', 'high_protein', 'weight_loss'];
        $complexity = 0;
        
        foreach ($goals as $goal) {
            if (in_array($goal, $complexGoals)) {
                $complexity += 0.3;
            } else {
                $complexity += 0.1;
            }
        }
        
        return min(1.0, $complexity);
    }
    
    private function getUserHistoryFactor(User $user): float
    {
        // Placeholder - would analyze user's actual nutrition history
        return 0.1; // Small positive boost
    }
    
    private function getAdherenceRecommendations(float $probability): array
    {
        if ($probability > 0.8) {
            return ['Excellentes chances de succès', 'Maintenir la motivation'];
        } elseif ($probability > 0.6) {
            return ['Bonnes chances avec un suivi régulier', 'Planifier les repas à l\'avance'];
        } else {
            return ['Commencer graduellement', 'Chercher du soutien', 'Fixer des objectifs plus modestes'];
        }
    }
    
    private function getPersonalizationLevel(int $percentage): string
    {
        if ($percentage >= 80) return 'Très personnalisé';
        if ($percentage >= 60) return 'Bien personnalisé';
        if ($percentage >= 40) return 'Moyennement personnalisé';
        return 'Peu personnalisé';
    }
    
    private function getUserExperienceLevel(User $user): float
    {
        // Placeholder - would analyze user's experience
        return 0.5; // Medium experience
    }
    
    private function getDifficultyLevel(int $difficulty): string
    {
        if ($difficulty >= 70) return 'Difficile';
        if ($difficulty >= 40) return 'Modéré';
        return 'Facile';
    }
    
    private function getDifficultyRecommendations(int $difficulty): array
    {
        if ($difficulty >= 70) {
            return ['Commencer progressivement', 'Chercher un accompagnement', 'Simplifier les premières semaines'];
        } elseif ($difficulty >= 40) {
            return ['Planification nécessaire', 'Suivi régulier recommandé'];
        } else {
            return ['Approche directe possible', 'Ajustements faciles'];
        }
    }
    
    /**
     * Calculate macro distribution based on goals
     */
    private function calculateMacroDistribution(array $goals): array
    {
        // Default balanced distribution
        $distribution = [
            'carbs_percentage' => 45,
            'protein_percentage' => 25,
            'fat_percentage' => 30
        ];
        
        // Adjust based on goals
        if (in_array('muscle_gain', $goals) || in_array('high_protein', $goals)) {
            $distribution['protein_percentage'] = 35;
            $distribution['carbs_percentage'] = 40;
            $distribution['fat_percentage'] = 25;
        } elseif (in_array('weight_loss', $goals)) {
            $distribution['protein_percentage'] = 30;
            $distribution['carbs_percentage'] = 35;
            $distribution['fat_percentage'] = 35;
        } elseif (in_array('keto', $goals)) {
            $distribution['protein_percentage'] = 25;
            $distribution['carbs_percentage'] = 5;
            $distribution['fat_percentage'] = 70;
        }
        
        return $distribution;
    }
    
    /**
     * Get recommended foods based on restrictions
     */
    private function getRecommendedFoods(array $restrictions, array $macroDistribution): array
    {
        $allFoods = $this->nutritionService->getFoodDatabase();
        $recommended = [];
        
        // Normalize restrictions for easier checking
        $activeRestrictions = array_map('strtolower', $restrictions);

        // Define macro-focused categories/properties for food selection
        $proteinFoods = [];
        $carbFoods = [];
        $fatFoods = [];

        foreach ($allFoods as $foodId => $food) {
            $foodData = array_merge(['id' => $foodId], $food); // Ensure ID is in the array
            $isCompatible = true;
            
            // Check dietary restrictions
            if (!empty($activeRestrictions)) {
                foreach ($activeRestrictions as $userRestriction) {
                    // If user states 'none' as a restriction, it implies no specific restrictions, so it doesn't exclude any food based on its attributes
                    if ($userRestriction === 'none') continue; 

                    // If the food has dietary restrictions, check for conflict with user's restrictions
                    if (isset($foodData['dietaryRestrictions']) && !empty($foodData['dietaryRestrictions'])) {
                        // If user wants 'vegan', only allow foods that are 'vegan'. If user wants 'gluten_free', only allow foods that are 'gluten_free'
                        // This assumes dietaryRestrictions on food are positive attributes. 
                        // If a food does NOT have an attribute that the user REQUIRES (e.g., user is vegan, but food is not marked vegan), then it's incompatible.
                        // For now, let's simplify: if a user specifies a restriction, food must POSSESS that restriction attribute to be compatible.
                        // This is a strict interpretation for a demo.
                        if (!in_array($userRestriction, array_map('strtolower', $foodData['dietaryRestrictions']))) {
                            $isCompatible = false;
                            break; // This food is not compatible with this user restriction
                        }
                    } else { // Food has no listed dietary restrictions
                        // If user has a restriction, but food has no attributes, assume it's not compatible unless restriction is very general
                        // For example, if user is 'vegan' and food has no dietary restrictions, it's incompatible
                        // Unless the userRestriction is 'none' (handled above) or 'balanced'
                        $isCompatible = false;
                        break; 
                    }
                }
            }
            
            if ($isCompatible) {
                // Categorize foods by macro dominance (simple heuristic)
                if (($foodData['protein'] ?? 0) > (($foodData['carbs'] ?? 0) + ($foodData['fat'] ?? 0))) {
                    $proteinFoods[] = $foodData;
                } elseif (($foodData['carbs'] ?? 0) > (($foodData['protein'] ?? 0) + ($foodData['fat'] ?? 0))) {
                    $carbFoods[] = $foodData;
                } else {
                    $fatFoods[] = $foodData;
                }
                $recommended[] = $foodData; // Also add to general recommended list
            }
        }

        // Sort recommended foods by calorie density or protein content for better meal planning options
        usort($recommended, function($a, $b) {
            return ($b['protein'] ?? 0) - ($a['protein'] ?? 0); // Prioritize higher protein foods
        });
        
        // Return a combination of top foods, potentially weighted by macro distribution emphasis
        // For now, return a diverse selection from the categorized foods
        $finalRecommendations = [];
        $numToPick = 5;

        // Pick some protein-rich foods
        shuffle($proteinFoods);
        $finalRecommendations = array_merge($finalRecommendations, array_slice($proteinFoods, 0, $numToPick));

        // Pick some carb-rich foods
        shuffle($carbFoods);
        $finalRecommendations = array_merge($finalRecommendations, array_slice($carbFoods, 0, $numToPick));

        // Pick some fat-rich foods
        shuffle($fatFoods);
        $finalRecommendations = array_merge($finalRecommendations, array_slice($fatFoods, 0, $numToPick));

        // Ensure uniqueness and limit overall count
        $uniqueRecommendations = [];
        $seenFoodIds = [];
        foreach ($finalRecommendations as $food) {
            if (!isset($seenFoodIds[$food['id']])) {
                $uniqueRecommendations[] = $food;
                $seenFoodIds[$food['id']] = true;
            }
        }
        
        return array_slice($uniqueRecommendations, 0, 20); // Limit to top 20 diverse foods
    }
    
    /**
     * Generate meal suggestions
     */
    private function generateMealSuggestions(float $calories, array $macros, array $restrictions): array
    {
        $suggestedMeals = [];
        $mealCalories = [
            'breakfast' => $calories * 0.25,
            'lunch' => $calories * 0.35,
            'dinner' => $calories * 0.30,
            'snacks' => $calories * 0.10,
        ];

        $allRecommendedFoods = $this->getRecommendedFoods($restrictions, $macros); // Get a larger pool of foods
        
        foreach ($mealCalories as $mealType => $targetCal) {
            $mealFoods = [];
            $currentCal = 0;
            $attemptedFoods = [];

            // Try to build meal with macro focus
            $foodsForMeal = $allRecommendedFoods; // Reset pool for each meal
            shuffle($foodsForMeal);

            foreach ($foodsForMeal as $food) {
                $foodCal = $food['calories'] ?? 0;
                
                // Avoid adding same food multiple times in one meal and exceeding calorie target significantly
                if (!in_array($food['id'], $attemptedFoods) && ($currentCal + $foodCal) <= ($targetCal * 1.5)) {
                    $mealFoods[] = ['id' => $food['id'], 'name' => $food['name'], 'calories' => $foodCal];
                    $currentCal += $foodCal;
                    $attemptedFoods[] = $food['id'];

                    if (count($mealFoods) >= 3) break; // Limit number of items per meal
                }
                if ($currentCal >= $targetCal * 0.8) break; // Stop if close to target
            }

            $suggestedMeals[$mealType] = [
                'target_calories' => round($targetCal),
                'suggested_foods' => array_column($mealFoods, 'name'),
                'description' => ucfirst($mealType) . ' équilibré' // Simple description
            ];
        }

        return $suggestedMeals;
    }
    
    /**
     * Generate nutrition tips
     */
    private function generateNutritionTips(
        array $goals,
        array $restrictions,
        array $userProfile, // Added user profile for more context
        array $adherenceProbability,
        array $difficultyAssessment
    ): array {
        $tips = [];

        // Basic hydration tip (always relevant)
        $tips[] = 'Buvez au moins 2.5L d\'eau par jour pour une hydratation optimale et un métabolisme efficace.';

        // General healthy eating tip
        $tips[] = 'Privilégiez les aliments complets, non transformés : fruits, légumes, céréales complètes, protéines maigres.';
        $tips[] = 'Répartissez vos apports protéiques sur la journée pour optimiser la synthèse musculaire et la satiété.';
        
        // Tips based on user goals
        if (in_array('muscle_gain', $goals)) {
            $tips[] = 'Pour la prise de muscle, visez 1.6-2.2g de protéines par kg de poids corporel.';
            $tips[] = 'Consommez des glucides et des protéines dans les 2 heures suivant votre entraînement pour une meilleure récupération.';
        }
        
        if (in_array('weight_loss', $goals)) {
            $tips[] = 'Créez un déficit calorique modéré (300-500 kcal) pour une perte de poids durable et saine.';
            $tips[] = 'Intégrez des aliments riches en fibres et en eau pour augmenter la satiété et réduire les fringales.';
            $tips[] = 'Privilégiez les aliments à haute densité nutritionnelle pour maximiser l\'apport en vitamines et minéraux.';
        }

        if (in_array('keto', $goals)) {
            $tips[] = 'Maintenez un apport très faible en glucides (<50g/jour) pour rester en cétose.';
            $tips[] = 'Assurez un apport suffisant en électrolytes (sodium, potassium, magnésium) pour prévenir la grippe céto.';
        }

        // Tips based on dietary restrictions
        if (!empty($restrictions)) {
            if (in_array('vegetarian', array_map('strtolower', $restrictions)) || in_array('vegan', array_map('strtolower', $restrictions))) {
                $tips[] = 'Explorez diverses sources de protéines végétales : légumineuses, tofu, tempeh, seitan, noix et graines.';
                $tips[] = 'Attention aux carences potentielles en B12, Fer, Calcium, Vitamine D. Envisagez une supplémentation si nécessaire.';
            }
            if (in_array('gluten_free', array_map('strtolower', $restrictions))) {
                $tips[] = 'Optez pour des céréales sans gluten comme le riz, le quinoa, le sarrasin ou le maïs.';
            }
        }

        // Tips based on difficulty assessment
        if (($difficultyAssessment['score'] ?? 0) >= 70) { // 'Difficile'
            $tips[] = 'Ce régime est ambitieux. Commencez par de petits changements, préparez vos repas à l\'avance et soyez indulgent avec vous-même.';
        } elseif (($difficultyAssessment['score'] ?? 0) >= 40) { // 'Modéré'
            $tips[] = 'Une bonne planification des repas et un suivi régulier vous aideront à maintenir ce régime modéré.';
        }

        // Tips based on adherence probability
        if (($adherenceProbability['probability'] ?? 0) < 0.6) {
            $tips[] = 'Votre probabilité d\'adhérence semble basse. Concentrez-vous sur la mise en place de routines simples et la recherche de soutien.';
        } elseif (($adherenceProbability['probability'] ?? 0) > 0.8) {
            $tips[] = 'Excellente probabilité d\'adhérence ! Continuez sur votre lancée et explorez de nouvelles recettes.';
        }

        // Tips based on lifestyle factors
        if (($userProfile['lifestyle_factors']['irregular_schedule'] ?? false)) {
            $tips[] = 'Avec un emploi du temps irrégulier, la préparation des repas (meal prep) est votre meilleure alliée pour la cohérence.';
        }
        if (($userProfile['lifestyle_factors']['time_constrained'] ?? false)) {
            $tips[] = 'Privilégiez des recettes rapides et des aliments nécessitant peu de préparation pour gagner du temps.';
        }

        // Ensure uniqueness and limit the number of tips for readability
        return array_slice(array_unique($tips), 0, 8); // Limit to top 8 diverse tips
    }

    /**
     * Analyze user profile for regime matching
     */
    private function analyzeUserProfile(User $user): array
    {
        $goals = $this->nutritionService->getNutritionGoals($user);
        
        return [
            'activity_level' => $this->determineActivityLevel($user),
            'dietary_preferences' => $this->extractDietaryPreferences($user),
            'calorie_needs' => $goals['calories'],
            'macro_preferences' => $this->analyzeMacroPreferences($user),
            'lifestyle_factors' => $this->assessLifestyleFactors($user),
            'health_considerations' => $this->evaluateHealthFactors($user),
            'experience_level' => $this->assessNutritionExperience($user),
            'time_availability' => $this->assessTimeAvailability($user),
            'budget_constraints' => $this->assessBudgetConstraints($user)
        ];
    }

    /**
     * Analyze user's nutrition history to understand patterns
     */
    private function analyzeNutritionHistory(User $user): array
    {
        $lastMonth = Carbon::now()->subDays(30);
        
        $entries = MealEntry::where('user_id', $user->id)
            ->where('created_at', '>=', $lastMonth)
            ->get();

        $patterns = [
            'consistency_score' => $this->calculateConsistencyScore($entries),
            'preferred_meal_times' => $this->identifyMealTimePatterns($entries),
            'macro_adherence' => $this->analyzeMacroAdherence($entries, $user),
            'food_variety_score' => $this->calculateFoodVarietyScore($entries),
            'weekend_vs_weekday' => $this->analyzeWeekendPatterns($entries),
            'challenge_areas' => $this->identifyChallengeAreas($entries, $user),
            'success_triggers' => $this->identifySuccessTriggers($entries)
        ];

        return $patterns;
    }

    /**
     * Get professional regimes.
     */
    public function getProfessionalRegimes(): array
    {
        // For now, this returns all available regimes.
        // It can be filtered later based on a specific flag if needed.
        return $this->getAvailableRegimes();
    }

    /**
     * Get available regimes from database or config
     */
    private function getAvailableRegimes(): array
    {
        return [
            [
                'id' => 'balanced_moderate',
                'name' => 'Équilibré Modéré',
                'description' => 'Régime équilibré adapté à un mode de vie actif modéré',
                'short_description' => 'Parfait pour commencer en douceur',
                'difficulty' => 'easy',
                'macro_split' => ['carbs' => 45, 'protein' => 25, 'fat' => 30],
                'restrictions' => [],
                'target_audience' => ['beginner', 'moderate_activity'],
                'key_features' => ['flexible', 'sustainable', 'balanced']
            ],
            [
                'id' => 'high_protein_performance',
                'name' => 'Performance Haute Protéine',
                'description' => 'Optimisé pour la performance sportive et la construction musculaire',
                'short_description' => 'Idéal pour les athlètes et sportifs',
                'difficulty' => 'moderate',
                'macro_split' => ['carbs' => 35, 'protein' => 35, 'fat' => 30],
                'restrictions' => [],
                'target_audience' => ['athlete', 'high_activity', 'muscle_gain'],
                'key_features' => ['performance', 'muscle_building', 'recovery']
            ],
            [
                'id' => 'flexible_lifestyle',
                'name' => 'Lifestyle Flexible',
                'description' => 'Adapté aux emplois du temps chargés et variables',
                'short_description' => 'S\'adapte à votre style de vie',
                'difficulty' => 'easy',
                'macro_split' => ['carbs' => 40, 'protein' => 30, 'fat' => 30],
                'restrictions' => [],
                'target_audience' => ['busy_lifestyle', 'irregular_schedule'],
                'key_features' => ['flexible', 'time_efficient', 'practical']
            ],
            [
                'id' => 'lean_cutting',
                'name' => 'Sèche Optimisée',
                'description' => 'Conçu pour la perte de graisse tout en préservant la masse musculaire',
                'short_description' => 'Pour un physique défini',
                'difficulty' => 'hard',
                'macro_split' => ['carbs' => 25, 'protein' => 40, 'fat' => 35],
                'restrictions' => [],
                'target_audience' => ['weight_loss', 'body_composition'],
                'key_features' => ['fat_loss', 'muscle_preservation', 'precise']
            ]
        ];
    }

    /**
     * Score regime compatibility with user profile
     */
    private function scoreRegimeCompatibility(array $regime, array $userProfile, array $preferences): float
    {
        $score = 0;
        $maxScore = 0;

        // Activity level compatibility
        $activityMatch = $this->matchActivityLevel($regime, $userProfile['activity_level']);
        $score += $activityMatch * 0.25;
        $maxScore += 0.25;

        // Experience level compatibility
        $experienceMatch = $this->matchExperienceLevel($regime, $userProfile['experience_level']);
        $score += $experienceMatch * 0.2;
        $maxScore += 0.2;

        // Lifestyle compatibility
        $lifestyleMatch = $this->matchLifestyle($regime, $userProfile['lifestyle_factors']);
        $score += $lifestyleMatch * 0.2;
        $maxScore += 0.2;

        // Macro preferences
        $macroMatch = $this->matchMacroPreferences($regime, $userProfile['macro_preferences']);
        $score += $macroMatch * 0.15;
        $maxScore += 0.15;

        // Time availability
        $timeMatch = $this->matchTimeAvailability($regime, $userProfile['time_availability']);
        $score += $timeMatch * 0.1;
        $maxScore += 0.1;

        // User preferences
        $preferencesMatch = $this->matchUserPreferences($regime, $preferences);
        $score += $preferencesMatch * 0.1;
        $maxScore += 0.1;

        return $maxScore > 0 ? $score / $maxScore : 0;
    }

    // Helper methods for scoring
    private function matchActivityLevel(array $regime, string $activityLevel): float
    {
        $activityMap = [
            'sedentary' => ['balanced_moderate', 'flexible_lifestyle'],
            'light' => ['balanced_moderate', 'flexible_lifestyle'],
            'moderate' => ['balanced_moderate', 'high_protein_performance'],
            'active' => ['high_protein_performance', 'lean_cutting'],
            'very_active' => ['high_protein_performance', 'lean_cutting']
        ];

        return in_array($regime['id'], $activityMap[$activityLevel] ?? []) ? 1.0 : 0.5;
    }

    private function matchExperienceLevel(array $regime, string $experienceLevel): float
    {
        $experienceMap = [
            'beginner' => ['easy'],
            'intermediate' => ['easy', 'moderate'],
            'advanced' => ['moderate', 'hard']
        ];

        return in_array($regime['difficulty'], $experienceMap[$experienceLevel] ?? []) ? 1.0 : 0.3;
    }

    private function matchLifestyle(array $regime, array $lifestyleFactors): float
    {
        $score = 0.5; // Base score

        if ($lifestyleFactors['irregular_schedule'] && $regime['id'] === 'flexible_lifestyle') {
            $score += 0.5;
        }

        if ($lifestyleFactors['time_constrained'] && in_array('time_efficient', $regime['key_features'])) {
            $score += 0.3;
        }

        return min($score, 1.0);
    }

    private function matchMacroPreferences(array $regime, array $macroPreferences): float
    {
        // Simple matching based on macro split preferences
        // For demo, let's assume a higher match if the regime's primary macro aligns with user's preference
        $regimeMacros = $regime['macro_split'] ?? ['carbs' => 0, 'protein' => 0, 'fat' => 0];
        $userProteinPref = $macroPreferences['protein_percentage'] ?? 0;
        $userCarbsPref = $macroPreferences['carbs_percentage'] ?? 0;
        $userFatPref = $macroPreferences['fat_percentage'] ?? 0;

        // A simple way to check alignment: compare top macro
        $regimeTopMacro = array_search(max($regimeMacros), $regimeMacros);
        $userTopMacro = array_search(max($userProteinPref, $userCarbsPref, $userFatPref), 
            ['protein' => $userProteinPref, 'carbs' => $userCarbsPref, 'fat' => $userFatPref]);
        
        if ($regimeTopMacro === $userTopMacro) {
            return 1.0; // High match
        } else {
            return 0.6; // Moderate match
        }
    }

    private function matchTimeAvailability(array $regime, array $timeAvailability): float
    {
        // Match regime complexity with available time
        // For demo, assume easy regimes are good for low time availability
        if (($timeAvailability['level'] ?? 'medium') === 'low' && $regime['difficulty'] === 'easy') {
            return 1.0;
        }
        return 0.7; // Placeholder
    }

    private function matchUserPreferences(array $regime, array $preferences): float
    {
        // Match specific user preferences with regime characteristics
        // For demo, assume a match if any of the user's goals align with regime's target audience
        $userGoals = $preferences['goals'] ?? [];
        $regimeTargetAudience = $regime['target_audience'] ?? [];
        
        foreach ($userGoals as $goal) {
            if (in_array($goal, $regimeTargetAudience)) {
                return 1.0; // High match
            }
        }
        return 0.8; // Placeholder
    }

    // Placeholder methods for detailed implementation
    private function determineActivityLevel(User $user): string 
    {
        // In a real application, this would fetch from user settings or activity logs.
        // For now, let's use a simple lookup based on a dummy user preference or return a default.
        // Assuming 'activity_level' might be in user's preferences if set up elsewhere
        // For demonstration, we'll return 'active' for users with ID 1, 'moderate' otherwise.
        if ($user->id === 1) {
            return 'active';
        }
        return 'moderate';
    }
    private function extractDietaryPreferences(User $user): array 
    {
        // In a real app, this would come from user settings.
        // For demo, combine user's restrictions from preferences with some defaults.
        $preferences = $user->profile->preferences ?? []; // Assuming user profile stores preferences
        $dietaryRestrictions = $preferences['restrictions'] ?? [];
        
        // Add default restrictions if none are explicitly set for the user (e.g., non-vegetarian)
        if (empty($dietaryRestrictions)) {
            return ['none']; // Or a more intelligent default
        }
        return $dietaryRestrictions;
    }
    private function analyzeMacroPreferences(User $user): array 
    {
        // In a real app, this would analyze user's historical macro intake or stated preferences.
        // For demo, use current nutrition goals if available, otherwise a balanced default.
        $goals = $this->nutritionService->getNutritionGoals($user);
        
        if (!empty($goals) && isset($goals['protein']) && isset($goals['carbs']) && isset($goals['fat'])) {
            // Calculate percentages based on goals (rough estimation)
            $totalMacroGoal = $goals['protein'] * 4 + $goals['carbs'] * 4 + $goals['fat'] * 9; // approximate calories from macros
            if ($totalMacroGoal > 0) {
                return [
                    'protein_percentage' => round(($goals['protein'] * 4 / $totalMacroGoal) * 100),
                    'carbs_percentage' => round(($goals['carbs'] * 4 / $totalMacroGoal) * 100),
                    'fat_percentage' => round(($goals['fat'] * 9 / $totalMacroGoal) * 100),
                ];
            }
        }
        
        // Default balanced macro preferences
        return ['protein_percentage' => 25, 'carbs_percentage' => 50, 'fat_percentage' => 25];
    }
    private function assessLifestyleFactors(User $user): array { 
        return ['irregular_schedule' => false, 'time_constrained' => false]; 
    }
    private function evaluateHealthFactors(User $user): array { return []; }
    private function assessNutritionExperience(User $user): string 
    {
        // Placeholder: In a real app, this would be based on user history, completed regimes, etc.
        // For demonstration, let's say users with ID 1 are 'advanced', others are 'intermediate'.
        if ($user->id === 1) {
            return 'advanced';
        }
        return 'intermediate';
    }
    private function assessTimeAvailability(User $user): array { return []; }
    private function assessBudgetConstraints(User $user): array { return []; }
    
    private function calculateConsistencyScore(object $entries): float { return 0.8; }
    private function identifyMealTimePatterns(object $entries): array { return []; }
    private function analyzeMacroAdherence(object $entries, User $user): array { 
        return ['protein' => 0.85, 'carbs' => 0.9, 'fat' => 0.8]; 
    }
    private function calculateFoodVarietyScore(object $entries): float { return 0.7; }
    private function analyzeWeekendPatterns(object $entries): array { return []; }
    private function identifyChallengeAreas(object $entries, User $user): array { return []; }
    private function identifySuccessTriggers(object $entries): array { return []; }
    
    private function generateAdherenceInsights(array $adherenceScore, array $nutritionHistory): array 
    { 
        return [
            'consistency_trends' => 'Amélioration constante ces dernières semaines',
            'best_performing_days' => ['monday', 'tuesday', 'wednesday'],
            'challenge_periods' => ['weekend', 'evening_snacks'],
            'recommendations' => [
                'Planifiez vos weekends à l\'avance',
                'Préparez des collations saines pour le soir',
                'Maintenez votre routine de la semaine'
            ]
        ];
    }
}