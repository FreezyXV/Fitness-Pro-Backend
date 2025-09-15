<?php

namespace App\Services;

use App\Models\User;
use App\Models\NutritionGoal;
use App\Models\MealEntry;
use App\Models\WaterIntake;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NutritionService
{
    protected RegimeRecommendationService $regimeRecommendationService;

    public function __construct(
        RegimeRecommendationService $regimeRecommendationService
    ) {
        $this->regimeRecommendationService = $regimeRecommendationService;
    }

    /**
     * Get daily nutrition data for a user.
     */
    public function getDailyNutrition(User $user, string $date): array
    {
        $carbonDate = Carbon::parse($date)->toDateString();

        // Get meal entries for the day
        $mealEntries = $this->getMealEntries($user, $date);

        // Calculate totals
        $totalCalories = $mealEntries->sum('calories');
        $totalProtein = $mealEntries->sum('protein');
        $totalCarbs = $mealEntries->sum('carbs');
        $totalFat = $mealEntries->sum('fat');
        $totalFiber = $mealEntries->sum('fiber');
        $totalSodium = $mealEntries->sum('sodium');
        $totalPotassium = $mealEntries->sum('potassium');
        $totalVitaminC = $mealEntries->sum('vitamin_c');

        // Get user's nutrition goals
        $nutritionGoal = $this->getNutritionGoals($user);
        $goals = $nutritionGoal ? $nutritionGoal->toArray() : ['calories' => 2000, 'protein' => 150, 'carbs' => 250, 'fat' => 70, 'water' => 3.0, 'fiber' => 25, 'sodium' => 2300, 'potassium' => 3500, 'vitaminC' => 90]; // Default goals

        // Calculate completion percentage (simple example)
        $completionPercentage = 0;
        if ($goals['calories'] > 0) { // Ensure calories goal is not zero to avoid division by zero
            $completionPercentage = round(($totalCalories / $goals['calories']) * 100);
        }

        return [
            'date' => $carbonDate,
            'totalCalories' => $totalCalories,
            'totalProtein' => $totalProtein,
            'totalCarbs' => $totalCarbs,
            'totalFat' => $totalFat,
            'totalFiber' => $totalFiber,
            'totalWater' => 0, // This should come from a separate water tracking system
            'totalSodium' => $totalSodium,
            'totalPotassium' => $totalPotassium,
            'totalVitaminC' => $totalVitaminC,
            'meals' => $mealEntries->toArray(),
            'goals' => $goals,
            'completionPercentage' => $completionPercentage,
        ];
    }

    // ===================================================================
    // MEAL ENTRIES MANAGEMENT
    // ===================================================================

    /**
     * Get meal entries for a specific user and date.
     */
    public function getMealEntries(User $user, string $dateString): \Illuminate\Database\Eloquent\Collection
    {
        $date = Carbon::parse($dateString)->toDateString();
        return $user->mealEntries()->whereDate('date', $date)->get();
    }
    
    /**
     * Add a meal entry for a user.
     */
    public function addMealEntry(User $user, array $data): MealEntry
    {
        DB::beginTransaction();
        try {
            $mealEntry = $user->mealEntries()->create([
                'food_id' => $data['food_id'] ?? null, // Assuming food_id is for external database or internal enum
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'meal_type' => $data['meal_type'],
                'date' => Carbon::parse($data['date'])->toDateString(),
                'calories' => $data['calories'],
                'protein' => $data['protein'],
                'carbs' => $data['carbs'],
                'fat' => $data['fat'],
                'fiber' => $data['fiber'] ?? 0,
                'sodium' => $data['sodium'] ?? 0,
                'potassium' => $data['potassium'] ?? 0,
                'vitamin_c' => $data['vitamin_c'] ?? 0,
            ]);

            DB::commit();
            return $mealEntry;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('NutritionService: Failed to add meal entry', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw the exception
        }
    }
    
    /**
     * Update a meal entry.
     */
    public function updateMealEntry(User $user, MealEntry $mealEntry, array $data): MealEntry
    {
        if ($user->id !== $mealEntry->user_id) {
            throw new \Exception('Unauthorized to update this meal entry.');
        }

        DB::beginTransaction();
        try {
            $mealEntry->update([
                'food_id' => $data['food_id'] ?? $mealEntry->food_id,
                'name' => $data['name'] ?? $mealEntry->name,
                'quantity' => $data['quantity'] ?? $mealEntry->quantity,
                'meal_type' => $data['meal_type'] ?? $mealEntry->meal_type,
                'date' => isset($data['date']) ? Carbon::parse($data['date'])->toDateString() : $mealEntry->date,
                'calories' => $data['calories'] ?? $mealEntry->calories,
                'protein' => $data['protein'] ?? $mealEntry->protein,
                'carbs' => $data['carbs'] ?? $mealEntry->carbs,
                'fat' => $data['fat'] ?? $mealEntry->fat,
                'fiber' => $data['fiber'] ?? $mealEntry->fiber,
                'sodium' => $data['sodium'] ?? $mealEntry->sodium,
                'potassium' => $data['potassium'] ?? $mealEntry->potassium,
                'vitamin_c' => $data['vitamin_c'] ?? $mealEntry->vitamin_c,
            ]);

            DB::commit();
            return $mealEntry;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('NutritionService: Failed to update meal entry', [
                'user_id' => $user->id,
                'meal_entry_id' => $mealEntry->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw the exception
        }
    }
    
    /**
     * Delete a meal entry.
     */
    public function deleteMealEntry(User $user, MealEntry $mealEntry): void
    {
        if ($user->id !== $mealEntry->user_id) {
            throw new \Exception('Unauthorized to delete this meal entry.');
        }

        DB::beginTransaction();
        try {
            $mealEntry->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('NutritionService: Failed to delete meal entry', [
                'user_id' => $user->id,
                'meal_entry_id' => $mealEntry->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw the exception
        }
    }

    // ===================================================================
    // NUTRITION GOALS MANAGEMENT
    // ===================================================================
    
    /**
     * Get nutrition goals for a specific user.
     */
    public function getNutritionGoals(User $user): ?NutritionGoal
    {
        return $user->nutritionGoal; // Assuming a hasOne relationship
    }
    
    /**
     * Set or update nutrition goals for a user.
     */
    public function setNutritionGoals(User $user, array $data): NutritionGoal
    {
        DB::beginTransaction();
        try {
            // Cast relevant fields to float for consistency, if not already handled by model casts
            $data = $this->castNutritionGoalData($data);

            $goal = $user->nutritionGoal()->updateOrCreate(
                ['user_id' => $user->id],
                $data
            );

            DB::commit();
            return $goal;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('NutritionService: Failed to set nutrition goals', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw the exception
        }
    }
    
    /**
     * Helper to cast nutrition goal data to float for consistency.
     */
    private function castNutritionGoalData(array $data): array
    {
        foreach (['calories', 'protein', 'carbs', 'fat', 'water', 'fiber', 'sodium', 'potassium', 'vitaminC'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = (float) $data[$key];
            }
        }
        return $data;
    }

    /**
     * Get food nutritional values - comprehensive database
     * Using the same food database as the frontend for consistency
     */
    private function getFoodNutritionalValues(string $foodId, float $quantity): array
    {
        $foodDatabase = $this->getFoodDatabase();
        
        if (!isset($foodDatabase[$foodId])) {
            // Fallback for unknown foods
            return [
                'calories' => round(100 * ($quantity / 100)),
                'protein' => round(10 * ($quantity / 100), 1),
                'carbs' => round(15 * ($quantity / 100), 1),
                'fat' => round(5 * ($quantity / 100), 1),
                'fiber' => round(2 * ($quantity / 100), 1),
                'sodium' => round(50 * ($quantity / 100), 1),
                'potassium' => round(100 * ($quantity / 100), 1),
                'vitamin_c' => round(5 * ($quantity / 100), 1),
            ];
        }
        
        $food = $foodDatabase[$foodId];
        
        return [
            'calories' => round($food['calories'] * ($quantity / 100)),
            'protein' => round($food['protein'] * ($quantity / 100), 1),
            'carbs' => round($food['carbs'] * ($quantity / 100), 1),
            'fat' => round($food['fat'] * ($quantity / 100), 1),
            'fiber' => round($food['fiber'] * ($quantity / 100), 1),
            'sodium' => round($food['sodium'] * ($quantity / 100), 1),
            'potassium' => round($food['potassium'] * ($quantity / 100), 1),
            'vitamin_c' => round($food['vitaminC'] * ($quantity / 100), 1),
        ];
    }
    
    /**
     * Search foods by query and optional filters
     */
    public function searchFoods(string $query, array $filters = []): array
    {
        $foodDatabase = $this->getFoodDatabase();
        $results = [];
        
        foreach ($foodDatabase as $id => $food) {
            if (stripos($food['name'], $query) !== false || stripos($id, $query) !== false) {
                $results[] = array_merge(['id' => $id], $food);
            }
        }
        
        // Apply filters if provided
        if (!empty($filters)) {
            $results = $this->applyFoodFilters($results, $filters);
        }
        
        return array_slice($results, 0, 20); // Limit to 20 results
    }
    
    /**
     * Get food by ID
     */
    public function getFoodById(string $foodId): ?array
    {
        $foodDatabase = $this->getFoodDatabase();
        
        if (isset($foodDatabase[$foodId])) {
            return array_merge(['id' => $foodId], $foodDatabase[$foodId]);
        }
        
        return null;
    }
    
    /**
     * Apply filters to food search results
     */
    private function applyFoodFilters(array $foods, array $filters): array
    {
        return array_filter($foods, function($food) use ($filters) {
            if (isset($filters['maxCalories']) && $food['calories'] > $filters['maxCalories']) {
                return false;
            }
            if (isset($filters['minProtein']) && $food['protein'] < $filters['minProtein']) {
                return false;
            }
            if (isset($filters['category']) && $food['category'] !== $filters['category']) {
                return false;
            }
            if (isset($filters['restrictions']) && !empty($filters['restrictions'])) {
                $hasRestriction = false;
                foreach ($filters['restrictions'] as $restriction) {
                    if (in_array($restriction, $food['dietaryRestrictions'] ?? [])) {
                        $hasRestriction = true;
                        break;
                    }
                }
                if (!$hasRestriction) {
                    return false;
                }
            }
            return true;
        });
    }
    
    /**
     * Update water intake for a user
     */
    public function updateWaterIntake(User $user, array $data): array
    {
        $date = Carbon::parse($data['date'] ?? now())->toDateString();
        $amount = $data['amount'];
        
        // Create water intake entry
        WaterIntake::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'date' => $date,
            'timestamp' => now(),
        ]);
        
        // Get total water for the day
        $totalWater = WaterIntake::where('user_id', $user->id)
                                ->whereDate('date', $date)
                                ->sum('amount');
        
        return [
            'amount_added' => $amount,
            'total_water' => $totalWater,
            'date' => $date,
        ];
    }
    
    /**
     * Get food categories
     */
    public function getFoodCategories(): array
    {
        return [
            'proteins' => 'Protéines',
            'seafood' => 'Fruits de mer',
            'dairy' => 'Produits laitiers',
            'vegetables' => 'Légumes',
            'fruits' => 'Fruits',
            'grains' => 'Céréales',
            'nuts_seeds' => 'Noix et graines',
            'legumes' => 'Légumineuses',
            'oils_fats' => 'Huiles et graisses',
            'beverages' => 'Boissons',
            'supplements' => 'Compléments'
        ];
    }

    /**
     * Get the comprehensive food database
     * This mirrors the frontend food-database.ts for consistency
     */
    public function getFoodDatabase(): array
    {
        return [
            // PROTEINS - MEAT & POULTRY
            'chicken_breast' => [
                'name' => 'Blanc de poulet',
                'nameEn' => 'Chicken breast',
                'category' => 'proteins',
                'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6,
                'fiber' => 0, 'sodium' => 74, 'potassium' => 256, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'medium',
                'dietaryRestrictions' => ['high_protein'], 
                'imageUrl' => '/assets/Aliments/poulet.png'
            ],
            'turkey_breast' => [
                'name' => 'Blanc de dinde',
                'nameEn' => 'Turkey breast',
                'category' => 'proteins',
                'calories' => 135, 'protein' => 30.1, 'carbs' => 0, 'fat' => 1,
                'fiber' => 0, 'sodium' => 1040, 'potassium' => 417, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'medium',
                'dietaryRestrictions' => ['high_protein'],
                'imageUrl' => '/assets/Aliments/dinde.png'
            ],
            'lean_beef' => [
                'name' => 'Bœuf maigre',
                'nameEn' => 'Lean beef',
                'category' => 'proteins',
                'calories' => 250, 'protein' => 26, 'carbs' => 0, 'fat' => 15,
                'fiber' => 0, 'sodium' => 72, 'potassium' => 318, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'low',
                'dietaryRestrictions' => ['high_protein'],
                'imageUrl' => '/assets/Aliments/boeuf.png'
            ],
            
            // SEAFOOD
            'salmon' => [
                'name' => 'Saumon',
                'nameEn' => 'Salmon',
                'category' => 'seafood',
                'calories' => 208, 'protein' => 22, 'carbs' => 0, 'fat' => 13,
                'fiber' => 0, 'sodium' => 44, 'potassium' => 363, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['high_protein'],
                'imageUrl' => '/assets/Aliments/saumon.png'
            ],
            'tuna' => [
                'name' => 'Thon',
                'nameEn' => 'Tuna',
                'category' => 'seafood',
                'calories' => 184, 'protein' => 30, 'carbs' => 0, 'fat' => 6.3,
                'fiber' => 0, 'sodium' => 39, 'potassium' => 441, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'medium',
                'dietaryRestrictions' => ['high_protein'],
                'imageUrl' => '/assets/Aliments/thon.png'
            ],
            'cod' => [
                'name' => 'Cabillaud',
                'nameEn' => 'Cod',
                'category' => 'seafood',
                'calories' => 105, 'protein' => 23, 'carbs' => 0, 'fat' => 0.9,
                'fiber' => 0, 'sodium' => 78, 'potassium' => 413, 'vitaminC' => 1,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['high_protein', 'low_fat'],
                'imageUrl' => '/assets/Aliments/cabillaud.png'
            ],
            
            // DAIRY
            'eggs' => [
                'name' => 'Œufs',
                'nameEn' => 'Eggs',
                'category' => 'dairy',
                'calories' => 155, 'protein' => 13, 'carbs' => 1.1, 'fat' => 11,
                'fiber' => 0, 'sodium' => 124, 'potassium' => 126, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegetarian', 'high_protein'],
                'imageUrl' => '/assets/Aliments/oeufs.png'
            ],
            'greek_yogurt' => [
                'name' => 'Yaourt grec',
                'nameEn' => 'Greek yogurt',
                'category' => 'dairy',
                'calories' => 100, 'protein' => 10, 'carbs' => 6, 'fat' => 5,
                'fiber' => 0, 'sodium' => 36, 'potassium' => 141, 'vitaminC' => 0.8,
                'verified' => true, 'sustainability' => 'medium',
                'dietaryRestrictions' => ['vegetarian'],
                'imageUrl' => '/assets/Aliments/yaourt-grec.png'
            ],
            'cottage_cheese' => [
                'name' => 'Fromage blanc',
                'nameEn' => 'Cottage cheese',
                'category' => 'dairy',
                'calories' => 98, 'protein' => 11, 'carbs' => 3.4, 'fat' => 4.3,
                'fiber' => 0, 'sodium' => 364, 'potassium' => 104, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'medium',
                'dietaryRestrictions' => ['vegetarian', 'high_protein'],
                'imageUrl' => '/assets/Aliments/fromage-blanc.png'
            ],
            
            // VEGETABLES
            'broccoli' => [
                'name' => 'Brocoli',
                'nameEn' => 'Broccoli',
                'category' => 'vegetables',
                'calories' => 55, 'protein' => 3.7, 'carbs' => 11, 'fat' => 0.6,
                'fiber' => 3.8, 'sodium' => 41, 'potassium' => 325, 'vitaminC' => 90,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/brocoli.png'
            ],
            'spinach' => [
                'name' => 'Épinards',
                'nameEn' => 'Spinach',
                'category' => 'vegetables',
                'calories' => 41, 'protein' => 5.4, 'carbs' => 7.3, 'fat' => 0.8,
                'fiber' => 4.3, 'sodium' => 70, 'potassium' => 466, 'vitaminC' => 70,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/epinards.png'
            ],
            'sweet_potato' => [
                'name' => 'Patate douce',
                'nameEn' => 'Sweet potato',
                'category' => 'vegetables',
                'calories' => 112, 'protein' => 1.6, 'carbs' => 26, 'fat' => 0.1,
                'fiber' => 3.9, 'sodium' => 6, 'potassium' => 230, 'vitaminC' => 19.6,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/patate-douce.png'
            ],
            'asparagus' => [
                'name' => 'Asperges',
                'nameEn' => 'Asparagus',
                'category' => 'vegetables',
                'calories' => 20, 'protein' => 2.2, 'carbs' => 3.9, 'fat' => 0.1,
                'fiber' => 2.1, 'sodium' => 2, 'potassium' => 202, 'vitaminC' => 5.6,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/asperges.png'
            ],
            
            // FRUITS
            'apple' => [
                'name' => 'Pomme',
                'nameEn' => 'Apple',
                'category' => 'fruits',
                'calories' => 95, 'protein' => 0.5, 'carbs' => 25, 'fat' => 0.3,
                'fiber' => 4.4, 'sodium' => 2, 'potassium' => 195, 'vitaminC' => 8.4,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/pomme.png'
            ],
            'banana' => [
                'name' => 'Banane',
                'nameEn' => 'Banana',
                'category' => 'fruits',
                'calories' => 105, 'protein' => 1.3, 'carbs' => 27, 'fat' => 0.4,
                'fiber' => 3.1, 'sodium' => 1, 'potassium' => 422, 'vitaminC' => 10.3,
                'verified' => true, 'sustainability' => 'medium',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/banane.png'
            ],
            'berries_mixed' => [
                'name' => 'Fruits rouges mélangés',
                'nameEn' => 'Mixed berries',
                'category' => 'fruits',
                'calories' => 57, 'protein' => 1.4, 'carbs' => 11, 'fat' => 0.7,
                'fiber' => 5.3, 'sodium' => 3, 'potassium' => 151, 'vitaminC' => 58.8,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/fruits-rouges.png'
            ],
            'avocado' => [
                'name' => 'Avocat',
                'nameEn' => 'Avocado',
                'category' => 'fruits',
                'calories' => 234, 'protein' => 4.0, 'carbs' => 17, 'fat' => 21.4,
                'fiber' => 13.5, 'sodium' => 11, 'potassium' => 690, 'vitaminC' => 17.1,
                'verified' => true, 'sustainability' => 'medium',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/avocat.png'
            ],
            
            // GRAINS
            'rice_brown' => [
                'name' => 'Riz complet',
                'nameEn' => 'Brown rice',
                'category' => 'grains',
                'calories' => 216, 'protein' => 5, 'carbs' => 45, 'fat' => 1.8,
                'fiber' => 3.5, 'sodium' => 7, 'potassium' => 174, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/riz-complet.png'
            ],
            'quinoa' => [
                'name' => 'Quinoa',
                'nameEn' => 'Quinoa',
                'category' => 'grains',
                'calories' => 222, 'protein' => 8.1, 'carbs' => 39, 'fat' => 3.6,
                'fiber' => 5.2, 'sodium' => 13, 'potassium' => 318, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'gluten_free', 'high_protein'],
                'imageUrl' => '/assets/Aliments/quinoa.png'
            ],
            'oats' => [
                'name' => 'Avoine',
                'nameEn' => 'Oats',
                'category' => 'grains',
                'calories' => 389, 'protein' => 16.9, 'carbs' => 66.3, 'fat' => 6.9,
                'fiber' => 10.6, 'sodium' => 2, 'potassium' => 429, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'high_protein'],
                'imageUrl' => '/assets/Aliments/avoine.png'
            ],
            
            // NUTS & SEEDS
            'almonds' => [
                'name' => 'Amandes',
                'nameEn' => 'Almonds',
                'category' => 'nuts_seeds',
                'calories' => 576, 'protein' => 21.2, 'carbs' => 21.6, 'fat' => 49.9,
                'fiber' => 12.5, 'sodium' => 1, 'potassium' => 733, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'medium',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free', 'high_protein'],
                'imageUrl' => '/assets/Aliments/amandes.png'
            ],
            'walnuts' => [
                'name' => 'Noix',
                'nameEn' => 'Walnuts',
                'category' => 'nuts_seeds',
                'calories' => 654, 'protein' => 15.2, 'carbs' => 13.7, 'fat' => 65.2,
                'fiber' => 6.7, 'sodium' => 2, 'potassium' => 441, 'vitaminC' => 1.3,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/noix.png'
            ],
            'chia_seeds' => [
                'name' => 'Graines de chia',
                'nameEn' => 'Chia seeds',
                'category' => 'nuts_seeds',
                'calories' => 486, 'protein' => 16.5, 'carbs' => 42.1, 'fat' => 30.7,
                'fiber' => 34.4, 'sodium' => 16, 'potassium' => 407, 'vitaminC' => 1.6,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free', 'high_protein'],
                'imageUrl' => '/assets/Aliments/chia.png'
            ],
            
            // LEGUMES
            'chickpeas' => [
                'name' => 'Pois chiches',
                'nameEn' => 'Chickpeas',
                'category' => 'legumes',
                'calories' => 164, 'protein' => 8.9, 'carbs' => 27.4, 'fat' => 2.6,
                'fiber' => 7.6, 'sodium' => 7, 'potassium' => 291, 'vitaminC' => 1.3,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/pois-chiches.png'
            ],
            'black_beans' => [
                'name' => 'Haricots noirs',
                'nameEn' => 'Black beans',
                'category' => 'legumes',
                'calories' => 132, 'protein' => 8.9, 'carbs' => 23, 'fat' => 0.5,
                'fiber' => 8.7, 'sodium' => 2, 'potassium' => 355, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/haricots-noirs.png'
            ],
            'lentils' => [
                'name' => 'Lentilles',
                'nameEn' => 'Lentils',
                'category' => 'legumes',
                'calories' => 116, 'protein' => 9, 'carbs' => 20, 'fat' => 0.4,
                'fiber' => 7.9, 'sodium' => 2, 'potassium' => 369, 'vitaminC' => 1.5,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'gluten_free', 'high_protein'],
                'imageUrl' => '/assets/Aliments/lentilles.png'
            ],
            
            // OILS & HEALTHY FATS
            'olive_oil' => [
                'name' => 'Huile d\'olive',
                'nameEn' => 'Olive oil',
                'category' => 'oils_fats',
                'calories' => 884, 'protein' => 0, 'carbs' => 0, 'fat' => 100,
                'fiber' => 0, 'sodium' => 2, 'potassium' => 1, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/huile-olive.png'
            ],
            
            // BEVERAGES
            'water' => [
                'name' => 'Eau',
                'nameEn' => 'Water',
                'category' => 'beverages',
                'calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0,
                'fiber' => 0, 'sodium' => 0, 'potassium' => 0, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/eau.png'
            ],
            'green_tea' => [
                'name' => 'Thé vert',
                'nameEn' => 'Green tea',
                'category' => 'beverages',
                'calories' => 2, 'protein' => 0.2, 'carbs' => 0.5, 'fat' => 0,
                'fiber' => 0, 'sodium' => 3, 'potassium' => 19, 'vitaminC' => 0.3,
                'verified' => true, 'sustainability' => 'high',
                'dietaryRestrictions' => ['vegan', 'vegetarian', 'keto', 'paleo', 'gluten_free'],
                'imageUrl' => '/assets/Aliments/the-vert.png'
            ],
            
            // PROTEIN SUPPLEMENTS
            'whey_protein' => [
                'name' => 'Protéine de lactosérum',
                'nameEn' => 'Whey protein',
                'category' => 'supplements',
                'calories' => 103, 'protein' => 20, 'carbs' => 2, 'fat' => 1.5,
                'fiber' => 0, 'sodium' => 50, 'potassium' => 150, 'vitaminC' => 0,
                'verified' => true, 'sustainability' => 'medium',
                'dietaryRestrictions' => ['vegetarian', 'high_protein'],
                'imageUrl' => '/assets/Aliments/whey.png'
            ]
        ];
    }

    /**
     * Get meal templates.
     */
    public function getMealTemplates(): array
    {
        // For now, return a placeholder array of meal templates.
        // This can be expanded to fetch from a database table in the future.
        return [
            [
                'id' => 'template_1',
                'name' => 'Petit-déjeuner riche en protéines',
                'description' => 'Un excellent début de journée pour la construction musculaire.',
                'meal_type' => 'breakfast',
                'aliments' => [
                    ['id' => 'eggs', 'quantity' => '3'],
                    ['id' => 'avocado', 'quantity' => '0.5'],
                    ['id' => 'spinach', 'quantity' => '50g']
                ]
            ],
            [
                'id' => 'template_2',
                'name' => 'Déjeuner équilibré',
                'description' => 'Un repas équilibré pour maintenir l\'énergie.',
                'meal_type' => 'lunch',
                'aliments' => [
                    ['id' => 'chicken_breast', 'quantity' => '150g'],
                    ['id' => 'rice_brown', 'quantity' => '100g'],
                    ['id' => 'broccoli', 'quantity' => '100g']
                ]
            ]
        ];
    }

    /**
     * Calculate Basal Metabolic Rate (BMR) using the Mifflin-St Jeor equation
     */
    private function calculateBMR($user): float
    {
        // Fallback values if user data is incomplete
        $weight = $user->weight ?? 70;
        $height = $user->height ?? 170;
        $age = $user->age ?? 25;
        $gender = $user->gender ?? 'male';

        if ($gender === 'male') {
            return 88.362 + (13.397 * $weight) + (4.799 * $height) - (5.677 * $age);
        } else {
            return 447.593 + (9.247 * $weight) + (3.098 * $height) - (4.330 * $age);
        }
    }

    /**
     * Calculate daily calorie needs based on activity level
     */
    private function calculateDailyCaloricNeeds($user): int
    {
        $bmr = $this->calculateBMR($user);
        $activityLevel = $user->activity_level ?? 'moderate';

        $activityMultipliers = [
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'active' => 1.725,
            'very_active' => 1.9
        ];

        $multiplier = $activityMultipliers[$activityLevel] ?? 1.55;

        return round($bmr * $multiplier);
    }

    /**
     * Calculate macronutrient breakdown
     */
    private function calculateMacroBreakdown(int $calories, array $percentages): array
    {
        return [
            'protein' => round(($calories * ($percentages['protein'] / 100)) / 4), // 4 kcal per gram
            'carbs' => round(($calories * ($percentages['carbs'] / 100)) / 4), // 4 kcal per gram
            'fat' => round(($calories * ($percentages['fat'] / 100)) / 9), // 9 kcal per gram
        ];
    }
}