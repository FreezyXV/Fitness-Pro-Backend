<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class ExerciseController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Display a listing of exercises with optional filtering and sorting
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('Exercises index called', $request->all());
            
            $query = Exercise::query();

            // Apply exercise-specific filters
            $this->applyExerciseFilters($query, $request);
            
            // Apply sorting
            $this->applyExerciseSorting($query, $request);

            // Get results with or without pagination
            $perPage = min((int) $request->get('per_page', $request->get('perPage', 0)), 100);
            
            if ($perPage > 0) {
                $exercises = $query->paginate($perPage);
                $data = [
                    'data' => $exercises->items(),
                    'pagination' => [
                        'current_page' => $exercises->currentPage(),
                        'last_page' => $exercises->lastPage(),
                        'per_page' => $exercises->perPage(),
                        'total' => $exercises->total()
                    ]
                ];
            } else {
                $exercises = $query->get();
                $data = $exercises;
            }

            Log::info('Exercises retrieved', ['count' => is_array($data) ? count($data) : (isset($data['pagination']) ? $data['pagination']['total'] : count($data))]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Exercices récupérés avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Exercise index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des exercices',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Display a specific exercise
     */
    public function show(int $id): JsonResponse
    {
        try {
            $exercise = Exercise::find($id);
            
            if (!$exercise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exercice non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $exercise,
                'message' => 'Exercice récupéré avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Exercise show error', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'exercice'
            ], 500);
        }
    }

    /**
     * Store a newly created exercise (protected route)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $payload = $request->validate([
                'name' => 'required|string|max:255',
                'body_part' => 'required|string|max:100',
                'description' => 'nullable|string',
                'muscle_groups' => 'nullable|array',
                'equipment_needed' => 'nullable|string|max:255',
                'video_url' => 'nullable|string|max:500',
                'duration' => 'nullable|integer|min:1|max:300',
                'difficulty' => 'required|in:beginner,intermediate,advanced',
                'instructions' => 'nullable|array',
                'tips' => 'nullable|array',
                'category' => 'nullable|string|max:100',
                'estimated_calories_per_minute' => 'nullable|integer|min:1',
            ]);

            $exercise = Exercise::create($payload);

            return response()->json([
                'success' => true,
                'data' => $exercise,
                'message' => 'Exercice créé avec succès'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Exercise store error', [
                'message' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'exercice'
            ], 500);
        }
    }

    /**
     * Update an existing exercise (protected route)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $exercise = Exercise::find($id);
            
            if (!$exercise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exercice non trouvé'
                ], 404);
            }

            $payload = $request->validate([
                'name' => 'sometimes|string|max:255',
                'body_part' => 'sometimes|string|max:100',
                'description' => 'nullable|string',
                'muscle_groups' => 'nullable|array',
                'equipment_needed' => 'nullable|string|max:255',
                'video_url' => 'nullable|string|max:500',
                'duration' => 'nullable|integer|min:1|max:300',
                'difficulty' => 'sometimes|in:beginner,intermediate,advanced',
                'instructions' => 'nullable|array',
                'tips' => 'nullable|array',
                'category' => 'nullable|string|max:100',
                'estimated_calories_per_minute' => 'nullable|integer|min:1',
            ]);

            $exercise->update($payload);

            return response()->json([
                'success' => true,
                'data' => $exercise,
                'message' => 'Exercice mis à jour avec succès'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Exercise update error', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'exercice'
            ], 500);
        }
    }

    /**
     * Remove an exercise (protected route)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $exercise = Exercise::find($id);
            
            if (!$exercise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exercice non trouvé'
                ], 404);
            }

            $exercise->delete();

            return response()->json([
                'success' => true,
                'message' => 'Exercice supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Exercise delete error', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'exercice'
            ], 500);
        }
    }

    /**
     * Toggle favorite status for an exercise (protected route)
     */
    public function toggleFavorite(int $id): JsonResponse
    {
        try {
            $exercise = Exercise::find($id);
            
            if (!$exercise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exercice non trouvé'
                ], 404);
            }

            // For now, just return success with mock data
            // Later you can implement user-specific favorites with a pivot table
            $isFavorite = !($exercise->is_favorite ?? false);
            
            return response()->json([
                'success' => true,
                'data' => ['is_favorite' => $isFavorite],
                'message' => $isFavorite ? 'Ajouté aux favoris' : 'Retiré des favoris'
            ]);

        } catch (\Exception $e) {
            Log::error('Toggle favorite error', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du favori'
            ], 500);
        }
    }

    /**
     * Get user's favorite exercises (protected route)
     */
    public function getFavorites(): JsonResponse
    {
        try {
            // For now, return empty array
            // Later implement with user relationship
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Favoris récupérés avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Get favorites error', ['message' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des favoris'
            ], 500);
        }
    }

    /**
     * Get distinct body parts with labels
     */
    public function getBodyParts(): JsonResponse
    {
        try {
            $bodyParts = Exercise::select('body_part')
                ->distinct()
                ->whereNotNull('body_part')
                ->orderBy('body_part')
                ->pluck('body_part')
                ->toArray();

            $labels = [
                'chest' => 'Poitrine',
                'back' => 'Dos',
                'arms' => 'Bras',
                'legs' => 'Jambes',
                'shoulders' => 'Épaules',
                'abs' => 'Abdominaux',
                'cardio' => 'Cardio',
                'mobility' => 'Mobilité',
                'flexibility' => 'Flexibilité'
            ];

            $result = [];
            foreach ($bodyParts as $bodyPart) {
                $count = Exercise::where('body_part', $bodyPart)->count();
                $result[] = [
                    'value' => $bodyPart,
                    'label' => $labels[$bodyPart] ?? ucfirst($bodyPart),
                    'count' => $count
                ];
            }

            Log::info('Body parts retrieved', ['count' => count($result)]);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Parties du corps récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Body parts error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des parties du corps',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Get categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = Exercise::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->orderBy('category')
                ->pluck('category')
                ->toArray();

            $labels = [
                'strength' => 'Force',
                'cardio' => 'Cardio',
                'flexibility' => 'Flexibilité',
                'hiit' => 'HIIT',
                'yoga' => 'Yoga',
                'mobility' => 'Mobilité'
            ];

            $result = [];
            foreach ($categories as $category) {
                $count = Exercise::where('category', $category)->count();
                $result[] = [
                    'value' => $category,
                    'label' => $labels[$category] ?? ucfirst($category),
                    'count' => $count
                ];
            }

            Log::info('Categories retrieved', ['count' => count($result)]);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Catégories récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Categories error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des catégories',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Get exercise statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $totalExercises = Exercise::count();
            
            $byBodyPart = Exercise::selectRaw('body_part, COUNT(*) as count')
                ->groupBy('body_part')
                ->orderBy('count', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'body_part' => $item->body_part,
                        'count' => $item->count
                    ];
                })
                ->toArray();

            $byDifficulty = Exercise::selectRaw('difficulty, COUNT(*) as count')
                ->groupBy('difficulty')
                ->orderByRaw("CASE difficulty WHEN 'beginner' THEN 1 WHEN 'intermediate' THEN 2 WHEN 'advanced' THEN 3 END")
                ->get()
                ->map(function($item) {
                    return [
                        'difficulty' => $item->difficulty,
                        'count' => $item->count
                    ];
                })
                ->toArray();

            $byCategory = Exercise::selectRaw('category, COUNT(*) as count')
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'category' => $item->category,
                        'count' => $item->count
                    ];
                })
                ->toArray();

            $withVideos = Exercise::whereNotNull('video_url')
                ->where('video_url', '!=', '')
                ->count();

            $avgDuration = Exercise::whereNotNull('duration')->avg('duration');
            $avgDuration = $avgDuration ? round($avgDuration, 2) : 0;

            $equipmentBreakdown = Exercise::selectRaw('equipment_needed, COUNT(*) as count')
                ->whereNotNull('equipment_needed')
                ->where('equipment_needed', '!=', '')
                ->groupBy('equipment_needed')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'equipment_needed' => $item->equipment_needed,
                        'count' => $item->count
                    ];
                })
                ->toArray();

            $stats = [
                'total_exercises' => $totalExercises,
                'by_body_part' => $byBodyPart,
                'by_difficulty' => $byDifficulty,
                'by_category' => $byCategory,
                'with_videos' => $withVideos,
                'avg_duration' => $avgDuration,
                'equipment_breakdown' => $equipmentBreakdown,
            ];

            Log::info('Stats retrieved', ['total_exercises' => $totalExercises]);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiques récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Stats error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Search exercises by name or description
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $limit = min((int) $request->get('limit', 10), 50);

            if (empty($query)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Aucun terme de recherche fourni'
                ]);
            }

            $exercises = Exercise::where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('body_part', 'like', "%{$query}%")
                      ->orWhere('category', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $exercises,
                'message' => 'Recherche terminée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Exercise search error', ['message' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche'
            ], 500);
        }
    }

    /**
     * Get related exercises
     */
    public function getRelated(int $id): JsonResponse
    {
        try {
            $exercise = Exercise::find($id);
            
            if (!$exercise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exercice non trouvé'
                ], 404);
            }

            $relatedExercises = Exercise::where('body_part', $exercise->body_part)
                ->where('id', '!=', $id)
                ->where('difficulty', $exercise->difficulty)
                ->limit(4)
                ->get();

            // If not enough, get from same body part with different difficulty
            if ($relatedExercises->count() < 4) {
                $additionalExercises = Exercise::where('body_part', $exercise->body_part)
                    ->where('id', '!=', $id)
                    ->where('difficulty', '!=', $exercise->difficulty)
                    ->limit(4 - $relatedExercises->count())
                    ->get();
                
                $relatedExercises = $relatedExercises->concat($additionalExercises);
            }

            return response()->json([
                'success' => true,
                'data' => $relatedExercises,
                'message' => 'Exercices similaires récupérés'
            ]);

        } catch (\Exception $e) {
            Log::error('Related exercises error', ['message' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des exercices similaires'
            ], 500);
        }
    }

    /**
     * Apply filters to the query
     */
    protected function applyExerciseFilters($query, Request $request): void
    {
        // Body part filter (support both naming conventions)
        $bodyPart = $request->get('body_part', $request->get('bodyPart'));
        if ($bodyPart && $bodyPart !== '' && $bodyPart !== 'all') {
            $query->where('body_part', $bodyPart);
        }

        // Difficulty filter
        $difficulty = $request->get('difficulty');
        if ($difficulty && $difficulty !== '' && $difficulty !== 'all') {
            $query->where('difficulty', $difficulty);
        }

        // Search filter
        $search = $request->get('search', '');
        if ($search !== '') {
            $query->where(function($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('body_part', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Category filter
        $category = $request->get('category');
        if ($category && $category !== '' && $category !== 'all') {
            $query->where('category', $category);
        }

        // Duration filter
        $duration = $request->get('duration');
        if ($duration && $duration !== '' && $duration !== 'all') {
            switch ($duration) {
                case 'short':
                    $query->where('duration', '<=', 15);
                    break;
                case 'medium':
                    $query->whereBetween('duration', [16, 30]);
                    break;
                case 'long':
                    $query->where('duration', '>', 30);
                    break;
            }
        }

        // Equipment filter
        $equipment = $request->get('equipment');
        if ($equipment && $equipment !== '' && $equipment !== 'all') {
            if ($equipment === 'none') {
                $query->where(function($sub) {
                    $sub->whereNull('equipment_needed')
                        ->orWhere('equipment_needed', '')
                        ->orWhere('equipment_needed', 'none')
                        ->orWhere('equipment_needed', 'aucun');
                });
            } else {
                $query->where('equipment_needed', 'like', "%{$equipment}%");
            }
        }

        // Muscle groups filter
        $muscleGroups = $request->get('muscle_groups');
        if ($muscleGroups && is_array($muscleGroups)) {
            foreach ($muscleGroups as $muscle) {
                $query->whereJsonContains('muscle_groups', $muscle);
            }
        }

        // Has video filter
        $hasVideo = $request->get('has_video');
        if ($hasVideo !== null) {
            if ($hasVideo === 'true' || $hasVideo === true || $hasVideo === '1') {
                $query->whereNotNull('video_url')->where('video_url', '!=', '');
            } else {
                $query->where(function($sub) {
                    $sub->whereNull('video_url')->orWhere('video_url', '');
                });
            }
        }
    }

    /**
     * Apply sorting to the query
     */
    protected function applyExerciseSorting($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', $request->get('sortBy', 'name'));
        $direction = $request->get('sort_direction', $request->get('sortDirection', 'asc'));
        
        // Validate direction
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';
        
        $allowedSorts = ['name', 'body_part', 'difficulty', 'duration', 'created_at', 'category'];
        
        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'difficulty') {
                // Custom sort order for difficulty
                $query->orderByRaw("CASE difficulty WHEN 'beginner' THEN 1 WHEN 'intermediate' THEN 2 WHEN 'advanced' THEN 3 END {$direction}");
            } else {
                $query->orderBy($sortBy, $direction);
            }
        } else {
            // Default sort
            $query->orderBy('name', 'asc');
        }
    }
}