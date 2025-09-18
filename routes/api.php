<?php
// routes/api.php 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NutritionController;
use App\Http\Controllers\AlimentController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes - SANCTUM COMPATIBLE VERSION WITH FIXED CALENDAR ROUTES
|--------------------------------------------------------------------------
*/

// =============================================
// 🔍 HEALTH & TEST ROUTES
// =============================================

Route::get('test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working correctly',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
        'version' => '2.0.0',
        'sanctum' => class_exists('Laravel\Sanctum\Sanctum') ? 'available' : 'not available'
    ]);
})->name('api.test');

// Frontend connection test endpoint (minimal response)
Route::get('connection-test', function () {
    return response()->json([
        'status' => 'connected',
        'timestamp' => now()->toISOString()
    ]);
})->name('api.connection-test');

// Clean API status endpoint for frontend (absolutely no errors)
Route::get('status', function () {
    // Suppress all possible errors
    error_reporting(0);
    ini_set('display_errors', 0);

    return response()->json([
        'api' => 'ready',
        'version' => '2.0.0',
        'timestamp' => now()->format('Y-m-d H:i:s')
    ], 200, [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'no-cache'
    ]);
})->name('api.status');

Route::get('health', function () {
    try {
        \DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    return response()->json([
        'status' => 'healthy',
        'api_version' => '2.0.0',
        'laravel_version' => app()->version(),
        'database' => $dbStatus,
        'sanctum' => [
            'available' => class_exists('Laravel\Sanctum\Sanctum'),
            'middleware' => 'configured'
        ],
        'cache' => config('cache.default'),
        'timestamp' => now()->toISOString()
    ]);
})->name('api.health');

Route::get('/version', function() {
    return response()->json([
        'api' => '2.0.0',
        'laravel' => app()->version(),
        'php' => PHP_VERSION
    ]);
})->name('api.version');

// Public endpoint to run migrations (temporary)
Route::post('/run-migrations', function () {
    try {
        \Artisan::call('migrate', ['--force' => true]);
        return response()->json([
            'success' => true,
            'message' => 'Migrations ran successfully',
            'output' => \Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to run migrations: ' . $e->getMessage()
        ], 500);
    }
})->name('run.migrations');

// Test endpoint to check video URL insertion
Route::post('/test-video-insert', function () {
    try {
        $exercise = \App\Models\Exercise::create([
            'name' => 'Test Exercise',
            'body_part' => 'chest',
            'category' => 'strength',
            'muscle_groups' => ['chest'],
            'difficulty' => 'beginner',
            'instructions' => ['Test instruction'],
            'duration' => 1,
            'video_url' => 'https://www.youtube.com/watch?v=test123',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test exercise created successfully',
            'exercise' => $exercise
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create test exercise: ' . $e->getMessage()
        ], 500);
    }
})->name('test.video.insert');

// Public endpoint to clear workouts for testing (temporary)
Route::post('/clear-workouts-public', function () {
    try {
        \App\Models\Workout::truncate();
        return response()->json([
            'success' => true,
            'message' => 'All workouts cleared successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to clear workouts: ' . $e->getMessage()
        ], 500);
    }
})->name('clear.workouts.public');

// Public endpoint to clear exercises for testing (temporary)
Route::post('/clear-exercises-public', function () {
    try {
        \App\Models\Exercise::truncate();
        return response()->json([
            'success' => true,
            'message' => 'All exercises cleared successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to clear exercises: ' . $e->getMessage()
        ], 500);
    }
})->name('clear.exercises.public');

// Public Portfolio Demo Seeding Endpoint for workouts
Route::post('/workouts-portfolio-seed', function () {
    try {
        // Check if workouts already exist to avoid duplicate seeding
        $workoutCount = \App\Models\Workout::count();
        if ($workoutCount > 0) {
            return response()->json([
                'success' => true,
                'message' => 'Workout demo data already exists',
                'workouts_count' => $workoutCount,
                'status' => 'already_seeded'
            ]);
        }

        // Get available exercises to use in workouts
        $exercises = \App\Models\Exercise::take(12)->get();
        if ($exercises->count() < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough exercises available. Please seed exercises first.',
                'exercises_count' => $exercises->count()
            ], 400);
        }

        // Demo workout templates for portfolio visitors (minimal fields only)
        $workoutTemplates = [
            [
                'name' => 'Entraînement Force Débutant',
                'description' => 'Programme de musculation parfait pour commencer',
                'actual_duration' => 45,
                'actual_calories' => 250,
                'is_template' => true,
                'user_id' => 1, // System user for demo data
                'exercises' => [
                    ['id' => $exercises[0]->id, 'sets' => 3, 'reps' => 12, 'rest_time_seconds' => 60],
                    ['id' => $exercises[1]->id, 'sets' => 3, 'reps' => 10, 'rest_time_seconds' => 90],
                    ['id' => $exercises[2]->id, 'sets' => 3, 'reps' => 15, 'rest_time_seconds' => 45],
                ]
            ],
            [
                'name' => 'HIIT Cardio Intense',
                'description' => 'Entraînement cardio haute intensité',
                'actual_duration' => 30,
                'actual_calories' => 350,
                'is_template' => true,
                'user_id' => 1,
                'exercises' => [
                    ['id' => $exercises[8]->id, 'sets' => 4, 'reps' => 20, 'rest_time_seconds' => 30],
                    ['id' => $exercises[9]->id, 'sets' => 3, 'reps' => 15, 'rest_time_seconds' => 45],
                    ['id' => $exercises[7]->id, 'sets' => 1, 'reps' => 1, 'rest_time_seconds' => 0], // Running
                ]
            ],
            [
                'name' => 'Flexibilité et Étirements',
                'description' => 'Routine de flexibilité pour récupération',
                'actual_duration' => 25,
                'actual_calories' => 120,
                'is_template' => true,
                'user_id' => 1,
                'exercises' => [
                    ['id' => $exercises[3]->id, 'sets' => 2, 'reps' => 12, 'rest_time_seconds' => 30],
                    ['id' => $exercises[4]->id, 'sets' => 2, 'reps' => 10, 'rest_time_seconds' => 30],
                ]
            ],
            [
                'name' => 'Full Body Avancé',
                'description' => 'Entraînement complet du corps pour niveau avancé',
                'actual_duration' => 60,
                'actual_calories' => 400,
                'is_template' => true,
                'user_id' => 1,
                'exercises' => [
                    ['id' => $exercises[5]->id, 'sets' => 4, 'reps' => 8, 'rest_time_seconds' => 120],
                    ['id' => $exercises[9]->id, 'sets' => 3, 'reps' => 12, 'rest_time_seconds' => 90],
                    ['id' => $exercises[10]->id, 'sets' => 3, 'reps' => 10, 'rest_time_seconds' => 90],
                    ['id' => $exercises[11]->id, 'sets' => 3, 'reps' => 12, 'rest_time_seconds' => 75],
                ]
            ],
        ];

        $inserted = 0;
        foreach ($workoutTemplates as $templateData) {
            // Remove exercises for now since pivot table schema is incompatible
            unset($templateData['exercises']);

            // Create the workout template
            $workout = \App\Models\Workout::create($templateData);
            $inserted++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Workout portfolio demo data seeded successfully',
            'workouts_created' => $inserted,
            'status' => 'freshly_seeded'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to seed workout portfolio demo data: ' . $e->getMessage(),
            'error' => $e->getTraceAsString()
        ], 500);
    }
})->name('workouts.portfolio.seed');

// Public workout templates endpoint for portfolio visitors
Route::get('/workouts/templates/public', function () {
    try {
        \Log::info('Attempting to get public workout templates');

        $templates = \App\Models\Workout::orderBy('created_at', 'desc')
            ->get();

        \Log::info('Found templates: ' . $templates->count());

        return response()->json([
            'success' => true,
            'data' => $templates,
            'message' => 'Public workout templates retrieved successfully'
        ]);
    } catch (\Exception $e) {
        \Log::error('Error getting public workout templates: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'Failed to get public workout templates: ' . $e->getMessage(),
            'error' => $e->getTraceAsString()
        ], 500);
    }
})->name('workouts.templates.public');

// Public individual workout template endpoint for portfolio visitors - force redeploy
Route::get('/workouts/templates/public/{id}', function ($id) {
    try {
        \Log::info('Attempting to get public workout template: ' . $id);

        $template = \App\Models\Workout::with([
            'exercises' => function($exerciseQuery) {
                $exerciseQuery->orderBy('workout_exercises.order_index', 'asc');
            }
        ])->findOrFail($id);

        \Log::info('Found template: ' . $template->name);

        return response()->json([
            'success' => true,
            'data' => $template,
            'message' => 'Public workout template retrieved successfully'
        ]);
    } catch (\Exception $e) {
        \Log::error('Error getting public workout template: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'Failed to get public workout template: ' . $e->getMessage(),
            'error' => $e->getTraceAsString()
        ], 500);
    }
})->where('id', '[0-9]+')->name('workouts.templates.public.show');

// Public goals endpoints for portfolio visitors
Route::get('/goals/public', function () {
    try {
        \Log::info('Attempting to get public goals');

        $goals = \App\Models\Goal::orderBy('created_at', 'desc')->get();

        \Log::info('Found goals: ' . $goals->count());

        return response()->json([
            'success' => true,
            'data' => $goals,
            'message' => 'Public goals retrieved successfully'
        ]);
    } catch (\Exception $e) {
        \Log::error('Error getting public goals: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'Failed to get public goals: ' . $e->getMessage(),
            'error' => $e->getTraceAsString()
        ], 500);
    }
})->name('goals.public');

// Public individual goal endpoint for portfolio visitors
Route::get('/goals/public/{id}', function ($id) {
    try {
        \Log::info('Attempting to get public goal: ' . $id);

        $goal = \App\Models\Goal::findOrFail($id);

        \Log::info('Found goal: ' . $goal->title);

        return response()->json([
            'success' => true,
            'data' => $goal,
            'message' => 'Public goal retrieved successfully'
        ]);
    } catch (\Exception $e) {
        \Log::error('Error getting public goal: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'Failed to get public goal: ' . $e->getMessage(),
            'error' => $e->getTraceAsString()
        ], 500);
    }
})->where('id', '[0-9]+')->name('goals.public.show');

// Force goals seeder endpoint with debug info
Route::post('/goals-seed', function () {
    try {
        $userCount = \App\Models\User::count();
        \Log::info('Users in database: ' . $userCount);

        \Artisan::call('db:seed', ['--class' => 'GoalsSeeder']);
        $goalCount = \App\Models\Goal::count();

        return response()->json([
            'success' => true,
            'message' => 'Goals seeded successfully',
            'users_count' => $userCount,
            'goals_created' => $goalCount,
            'status' => 'seeded'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to seed goals: ' . $e->getMessage(),
            'error' => $e->getTraceAsString()
        ], 500);
    }
})->name('api.goals-seed');

// Public Portfolio Demo Seeding Endpoint (for portfolio visitors)
Route::post('/portfolio-seed', function () {
    try {
        // Check if exercises already exist to avoid duplicate seeding
        $exerciseCount = \App\Models\Exercise::count();
        if ($exerciseCount > 0) {
            return response()->json([
                'success' => true,
                'message' => 'Portfolio demo data already exists',
                'exercises_count' => $exerciseCount,
                'status' => 'already_seeded'
            ]);
        }

        // Direct database seeding (production-safe) with video support
        $exercises = [
            // CHEST EXERCISES
            ['name' => 'Push-ups', 'body_part' => 'chest', 'category' => 'strength', 'muscle_groups' => ['chest', 'triceps', 'shoulders'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'instructions' => ['Start in plank position', 'Lower body until chest nearly touches ground', 'Push back up to starting position'], 'duration' => 1, 'estimated_calories' => 5, 'video_url' => 'https://www.youtube.com/watch?v=IODxDxX7oi4', 'thumbnail_url' => 'https://img.youtube.com/vi/IODxDxX7oi4/maxresdefault.jpg'],
            ['name' => 'Bench Press', 'body_part' => 'chest', 'category' => 'strength', 'muscle_groups' => ['chest', 'triceps', 'shoulders'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'instructions' => ['Lie on bench with barbell above chest', 'Lower barbell to chest with control', 'Press back up to starting position'], 'duration' => 1, 'estimated_calories' => 8, 'video_url' => 'https://www.youtube.com/watch?v=rT7DgCr-3pg', 'thumbnail_url' => 'https://img.youtube.com/vi/rT7DgCr-3pg/maxresdefault.jpg'],
            ['name' => 'Incline Dumbbell Press', 'body_part' => 'chest', 'category' => 'strength', 'muscle_groups' => ['chest', 'shoulders'], 'equipment' => 'dumbbells', 'difficulty' => 'intermediate', 'instructions' => ['Lie on incline bench with dumbbells', 'Press dumbbells up and together', 'Lower with control to starting position'], 'duration' => 1, 'estimated_calories' => 7, 'video_url' => 'https://www.youtube.com/watch?v=8iPEnn-ltC8', 'thumbnail_url' => 'https://img.youtube.com/vi/8iPEnn-ltC8/maxresdefault.jpg'],

            // LEG EXERCISES
            ['name' => 'Squats', 'body_part' => 'legs', 'category' => 'strength', 'muscle_groups' => ['quadriceps', 'glutes', 'calves'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'instructions' => ['Stand with feet shoulder-width apart', 'Lower hips back and down', 'Return to standing position'], 'duration' => 1, 'estimated_calories' => 6, 'video_url' => 'https://www.youtube.com/watch?v=aclHkVaku9U', 'thumbnail_url' => 'https://img.youtube.com/vi/aclHkVaku9U/maxresdefault.jpg'],
            ['name' => 'Lunges', 'body_part' => 'legs', 'category' => 'strength', 'muscle_groups' => ['quadriceps', 'glutes', 'hamstrings'], 'equipment' => 'bodyweight', 'difficulty' => 'beginner', 'instructions' => ['Step forward into lunge position', 'Lower back knee toward ground', 'Return to starting position'], 'duration' => 1, 'estimated_calories' => 5, 'video_url' => 'https://www.youtube.com/watch?v=QOVaHwm-Q6U', 'thumbnail_url' => 'https://img.youtube.com/vi/QOVaHwm-Q6U/maxresdefault.jpg'],
            ['name' => 'Deadlifts', 'body_part' => 'legs', 'category' => 'strength', 'muscle_groups' => ['hamstrings', 'glutes', 'back'], 'equipment' => 'barbell', 'difficulty' => 'advanced', 'instructions' => ['Stand with barbell at feet', 'Hip hinge movement to lift barbell', 'Return to ground with control'], 'duration' => 1, 'estimated_calories' => 10, 'video_url' => 'https://www.youtube.com/watch?v=XxWcirHIwVo', 'thumbnail_url' => 'https://img.youtube.com/vi/XxWcirHIwVo/maxresdefault.jpg'],

            // CARDIO EXERCISES
            ['name' => 'Running', 'body_part' => 'cardio', 'category' => 'cardio', 'muscle_groups' => ['legs', 'core'], 'equipment' => null, 'difficulty' => 'intermediate', 'instructions' => ['Maintain steady pace', 'Land on midfoot', 'Keep upright posture'], 'duration' => 30, 'estimated_calories' => 300, 'video_url' => 'https://www.youtube.com/watch?v=brFHyOtTwH4', 'thumbnail_url' => 'https://img.youtube.com/vi/brFHyOtTwH4/maxresdefault.jpg'],
            ['name' => 'Jump Rope', 'body_part' => 'cardio', 'category' => 'cardio', 'muscle_groups' => ['legs', 'shoulders', 'core'], 'equipment' => 'jump_rope', 'difficulty' => 'intermediate', 'instructions' => ['Jump with both feet', 'Rotate rope with wrists', 'Keep light on feet'], 'duration' => 10, 'estimated_calories' => 100, 'video_url' => 'https://www.youtube.com/watch?v=1BZM2Vre5oc', 'thumbnail_url' => 'https://img.youtube.com/vi/1BZM2Vre5oc/maxresdefault.jpg'],
            ['name' => 'Burpees', 'body_part' => 'cardio', 'category' => 'cardio', 'muscle_groups' => ['full_body'], 'equipment' => 'bodyweight', 'difficulty' => 'advanced', 'instructions' => ['Start standing', 'Squat and jump back to plank', 'Push-up, jump forward, jump up'], 'duration' => 1, 'estimated_calories' => 12, 'video_url' => 'https://www.youtube.com/watch?v=TU8QYVW0gDU', 'thumbnail_url' => 'https://img.youtube.com/vi/TU8QYVW0gDU/maxresdefault.jpg'],

            // BACK EXERCISES
            ['name' => 'Pull-ups', 'body_part' => 'back', 'category' => 'strength', 'muscle_groups' => ['back', 'biceps'], 'equipment' => 'pull_up_bar', 'difficulty' => 'intermediate', 'instructions' => ['Hang from bar with arms extended', 'Pull body up until chin over bar', 'Lower with control'], 'duration' => 1, 'estimated_calories' => 8, 'video_url' => 'https://www.youtube.com/watch?v=eGo4IYlbE5g', 'thumbnail_url' => 'https://img.youtube.com/vi/eGo4IYlbE5g/maxresdefault.jpg'],
            ['name' => 'Bent-over Rows', 'body_part' => 'back', 'category' => 'strength', 'muscle_groups' => ['back', 'biceps'], 'equipment' => 'barbell', 'difficulty' => 'intermediate', 'instructions' => ['Bend at hips with barbell', 'Pull barbell to lower chest', 'Lower with control'], 'duration' => 1, 'estimated_calories' => 7, 'video_url' => 'https://www.youtube.com/watch?v=FWJR5Ve8bnQ', 'thumbnail_url' => 'https://img.youtube.com/vi/FWJR5Ve8bnQ/maxresdefault.jpg'],
            ['name' => 'Lat Pulldowns', 'body_part' => 'back', 'category' => 'strength', 'muscle_groups' => ['back', 'biceps'], 'equipment' => 'cable_machine', 'difficulty' => 'beginner', 'instructions' => ['Sit at cable machine', 'Pull cable bar down to upper chest', 'Return with control'], 'duration' => 1, 'estimated_calories' => 6, 'video_url' => 'https://www.youtube.com/watch?v=CAwf7n6Luuc', 'thumbnail_url' => 'https://img.youtube.com/vi/CAwf7n6Luuc/maxresdefault.jpg'],
        ];

        $inserted = 0;
        foreach ($exercises as $exercise) {
            \App\Models\Exercise::create([
                'name' => $exercise['name'],
                'body_part' => $exercise['body_part'],
                'category' => $exercise['category'],
                'muscle_groups' => $exercise['muscle_groups'],
                'difficulty' => $exercise['difficulty'],
                'instructions' => $exercise['instructions'],
                'duration' => $exercise['duration'],
                'video_url' => $exercise['video_url'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $inserted++;
        }

        // Run the GoalsSeeder to add demo goals
        \Artisan::call('db:seed', ['--class' => 'GoalsSeeder']);
        $goalCount = \App\Models\Goal::count();

        return response()->json([
            'success' => true,
            'message' => 'Portfolio demo data seeded successfully',
            'exercises_created' => $inserted,
            'goals_created' => $goalCount,
            'status' => 'freshly_seeded'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to seed portfolio demo data: ' . $e->getMessage(),
            'error' => $e->getTraceAsString()
        ], 500);
    }
})->name('api.portfolio-seed');

// =============================================
// 🔐 PUBLIC AUTHENTICATION ROUTES
// =============================================
Route::prefix('auth')->name('api.auth.')->group(function () {
    // ROUTES PUBLIQUES - CORRESPONDANCE FRONTEND
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
    
    // Password reset endpoints
    Route::post('password/email', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::post('password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');
});

// =============================================
// 🏃‍♂️ PUBLIC EXERCISES ROUTES
// =============================================
Route::prefix('exercises')->name('api.exercises.')->group(function () {
    Route::get('/', [ExerciseController::class, 'index'])->name('index');
    Route::get('/search', [ExerciseController::class, 'search'])->name('search');
    Route::get('/body-parts', [ExerciseController::class, 'getBodyParts'])->name('body-parts');
    Route::get('/categories', [ExerciseController::class, 'getCategories'])->name('categories');
    Route::get('/stats', [ExerciseController::class, 'getStats'])->name('stats');
    Route::get('/validate-video', [ExerciseController::class, 'validateVideo'])->name('validate-video');
    Route::get('/{id}', [ExerciseController::class, 'show'])->where('id', '[0-9]+')->name('show');
    Route::get('/{id}/related', [ExerciseController::class, 'getRelated'])->where('id', '[0-9]+')->name('related');

    // Public favorites endpoint (returns empty if not authenticated)
    Route::get('/favorites', function (Request $request) {
        try {
            // Check if user is authenticated without throwing exceptions
            $user = $request->user('sanctum');

            if ($user) {
                return app(ExerciseController::class)->getFavorites($request);
            }

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Connectez-vous pour voir vos favoris'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Non authentifié'
            ]);
        }
    })->name('favorites-public');
});

// =============================================
// 🔒 PROTECTED ROUTES - SANCTUM MIDDLEWARE
// =============================================
Route::middleware('api.auth:sanctum')->group(function () {
    
    // =============================================
    // 🔐 AUTHENTICATED USER MANAGEMENT
    // =============================================
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('revoke-all', [AuthController::class, 'revokeAll'])->name('revoke-all');
    });
    
    // =============================================
    // 👤 USER PROFILE MANAGEMENT
    // =============================================
    Route::prefix('profile')->name('api.profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/photo', [ProfileController::class, 'updatePhoto'])->name('photo');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
    });
    
    // =============================================
    // 🏃‍♂️ PROTECTED EXERCISE MANAGEMENT
    // =============================================
    Route::prefix('exercises')->name('api.exercises.')->group(function () {
        Route::post('/', [ExerciseController::class, 'store'])->name('store');
        Route::put('/{id}', [ExerciseController::class, 'update'])->where('id', '[0-9]+')->name('update');
        Route::delete('/{id}', [ExerciseController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        
        // Favorites
        Route::post('/{id}/favorite', [ExerciseController::class, 'toggleFavorite'])->where('id', '[0-9]+')->name('favorite');
    });
    
    // =============================================
    // 💪 WORKOUT MANAGEMENT
    // =============================================
    Route::prefix('workouts')->name('api.workouts.')->group(function () {
        // Workout Templates
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [WorkoutController::class, 'getTemplates'])->name('index');
            Route::post('/', [WorkoutController::class, 'createTemplate'])->name('store');
            Route::get('/current', [WorkoutController::class, 'getCurrentTemplate'])->name('current');
            Route::get('/{id}', [WorkoutController::class, 'showTemplate'])->where('id', '[0-9]+')->name('show');
            Route::put('/{id}', [WorkoutController::class, 'updateTemplate'])->where('id', '[0-9]+')->name('update');
            Route::delete('/{id}', [WorkoutController::class, 'deleteTemplate'])->where('id', '[0-9]+')->name('destroy');
            Route::post('/{id}/duplicate', [WorkoutController::class, 'duplicateTemplate'])->where('id', '[0-9]+')->name('duplicate');
        });
        
        // Workout Logs (Sessions)
        Route::prefix('logs')->name('logs.')->group(function () {
            Route::get('/', [WorkoutController::class, 'getLogs'])->name('index');
            Route::post('/', [WorkoutController::class, 'logWorkout'])->name('store');
            Route::get('/{id}', [WorkoutController::class, 'showLog'])->where('id', '[0-9]+')->name('show');
            Route::delete('/{id}', [WorkoutController::class, 'deleteLog'])->where('id', '[0-9]+')->name('destroy');
            Route::post('/{id}/complete', [WorkoutController::class, 'completeWorkout'])->where('id', '[0-9]+')->name('complete');
        });

        Route::post('/start', [WorkoutController::class, 'startWorkout'])->name('start');
        
        // Workout Statistics
        Route::prefix('stats')->name('stats.')->group(function () {
            Route::get('/', [WorkoutController::class, 'getStats'])->name('index');
            Route::get('/weekly', [WorkoutController::class, 'getWeeklyStats'])->name('weekly');
            Route::get('/monthly', [WorkoutController::class, 'getMonthlyStats'])->name('monthly');
            Route::get('/consistency', [WorkoutController::class, 'getConsistency'])->name('consistency');
        });
    });
    
    // =============================================
    // 🎯 GOALS MANAGEMENT
    // =============================================
    Route::apiResource('goals', GoalController::class, ['as' => 'api'])->except(['create', 'edit']);
    Route::prefix('goals')->name('api.goals.')->group(function () {
        Route::post('/{goal}/progress', [GoalController::class, 'updateProgress'])->where('goal', '[0-9]+')->name('progress');
        Route::post('/{goal}/complete', [GoalController::class, 'markComplete'])->where('goal', '[0-9]+')->name('complete');
        Route::post('/{goal}/activate', [GoalController::class, 'activate'])->where('goal', '[0-9]+')->name('activate');
        Route::post('/{goal}/pause', [GoalController::class, 'pause'])->where('goal', '[0-9]+')->name('pause');
        Route::post('/{goal}/reset-status', [GoalController::class, 'resetGoalStatus'])->where('goal', '[0-9]+')->name('reset-status');
    });
    
    // =============================================
    // 📅 CALENDAR MANAGEMENT - FIXEDROUTES
    // =============================================
    Route::prefix('calendar')->name('api.calendar.')->group(function () {
        // Calendar Tasks CRUD
        Route::get('/tasks', [CalendarController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [CalendarController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{id}', [CalendarController::class, 'show'])->where('id', '[0-9]+')->name('tasks.show');
        Route::put('/tasks/{id}', [CalendarController::class, 'update'])->where('id', '[0-9]+')->name('tasks.update');
        Route::delete('/tasks/{id}', [CalendarController::class, 'destroy'])->where('id', '[0-9]+')->name('tasks.destroy');
        
        // Calendar Views - FIXED TO SUPPORT FRONTEND FORMAT
        Route::get('/today', [CalendarController::class, 'getTodayTasks'])->name('today');
        Route::get('/week', [CalendarController::class, 'getWeekTasks'])->name('week');
        Route::get('/month/{month}', [CalendarController::class, 'getMonthTasks'])->name('month');
        
        // ADDITIONAL ROUTE TO SUPPORT FRONTEND FORMAT
        Route::get('/tasks/month/{month}', [CalendarController::class, 'getMonthTasks'])->name('tasks.month');
        
        // Calendar Actions
        Route::post('/tasks/{id}/complete', [CalendarController::class, 'markComplete'])->where('id', '[0-9]+')->name('tasks.complete');
        Route::post('/tasks/{id}/incomplete', [CalendarController::class, 'markIncomplete'])->where('id', '[0-9]+')->name('tasks.incomplete');
        Route::post('/tasks/bulk', [CalendarController::class, 'bulkUpdate'])->name('tasks.bulk');
        
        // Calendar Statistics
        Route::get('/stats', [CalendarController::class, 'getStats'])->name('stats');

        // Workout Context for a specific date
        Route::get('/workout-context', [CalendarController::class, 'getWorkoutContext'])->name('workout-context');
    });
    
    // =============================================
    // 📊 DASHBOARD
    // =============================================
    Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
        Route::get('/performance', [DashboardController::class, 'getPerformanceData'])->name('performance');
        Route::get('/recent-activity', [DashboardController::class, 'getRecentActivity'])->name('recent-activity');
        Route::get('/monthly', [DashboardController::class, 'getMonthlyOverview'])->name('monthly');
        Route::get('/progress', [DashboardController::class, 'getProgress'])->name('progress');
        Route::get('/weekly-breakdown', [DashboardController::class, 'getWeeklyBreakdown'])->name('weekly-breakdown');
        Route::get('/quick-stats', [DashboardController::class, 'getQuickStats'])->name('quick-stats');
        Route::post('/clear-cache', [DashboardController::class, 'clearCache'])->name('clear-cache');
    });
    
    // =============================================
    // 🏆 ACHIEVEMENTS & SCORING SYSTEM
    // =============================================
    Route::prefix('achievements')->name('api.achievements.')->group(function () {
        Route::get('/', [AchievementController::class, 'index'])->name('index');
        Route::post('/check', [AchievementController::class, 'check'])->name('check');
    });
    
    // =============================================
    // 📊 USER SCORING SYSTEM
    // =============================================
    Route::prefix('user')->name('api.user.')->group(function () {
        Route::get('/score', [UserController::class, 'getUserScore'])->name('score');
        Route::get('/leaderboard', [UserController::class, 'getLeaderboard'])->name('leaderboard');
    });
    
    // =============================================
    // 🍎 NUTRITION
    // =============================================
    Route::prefix('nutrition')->name('nutrition.')->group(function () {
        // Daily summary and water intake
        Route::get('daily-summary/{date?} ', [NutritionController::class, 'getDailySummary'])->name('daily-summary');
        Route::post('water-intake', [NutritionController::class, 'updateWaterIntake'])->name('water-intake');

        // Meal Entry Management
        Route::get('meals/{date}', [NutritionController::class, 'getMealEntries'])->name('get-meals');
        Route::post('meals', [NutritionController::class, 'addMealEntry'])->name('add-meal');
        Route::put('meals/{mealEntry}', [NutritionController::class, 'updateMealEntry'])->name('update-meal');
        Route::delete('meals/{mealEntry}', [NutritionController::class, 'deleteMealEntry'])->name('delete-meal');

        // Nutrition Goal Management
        Route::get('goals', [NutritionController::class, 'getNutritionGoals'])->name('get-goals');
        Route::post('goals', [NutritionController::class, 'setNutritionGoals'])->name('set-goals');

        // Aliment & Regime (Food Database, Categories, Recommendations)
                Route::get('aliments', [AlimentController::class, 'index'])->name('aliments.index');
        Route::get('food-database', [NutritionController::class, 'getFoodDatabase'])->name('food-database');
        Route::get('food-categories', [NutritionController::class, 'getFoodCategories'])->name('food-categories');
        Route::post('/diet/generate', [NutritionController::class, 'generatePersonalizedDiet'])->name('generate-diet-and-recommendations');
        Route::post('/regimes/start', [NutritionController::class, 'startRegime'])->name('start-regime');
        Route::get('/regimes/current', [NutritionController::class, 'getCurrentRegime'])->name('current-regime');
        Route::post('/regimes/score', [NutritionController::class, 'updateRegimeScore'])->name('update-regime-score');
        Route::post('/regimes/pause', [NutritionController::class, 'pauseRegime'])->name('pause-regime');
        Route::post('/regimes/resume', [NutritionController::class, 'resumeRegime'])->name('resume-regime');
        Route::post('/regimes/complete', [NutritionController::class, 'completeRegime'])->name('complete-regime');
        Route::get('/regimes/history', [NutritionController::class, 'getRegimeHistory'])->name('regime-history');
        
        // New routes for professional regimes and meal templates
        Route::get('/regimes/professional', [NutritionController::class, 'getProfessionalRegimes'])->name('regimes.professional');
        Route::get('/meal-templates', [NutritionController::class, 'getMealTemplates'])->name('meal-templates');
        Route::get('/base-aliments', [AlimentController::class, 'getBaseAliments'])->name('base-aliments');
    });
    
    // =============================================
    // ⚡ PERFORMANCE & CACHE MANAGEMENT
    // =============================================
    Route::prefix('admin')->name('api.admin.')->middleware('throttle:10,1')->group(function () {
        Route::post('/clear-cache', function() {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        })->name('clear-cache');
        
        Route::get('/cache-info', function() {
            return response()->json([
                'success' => true,
                'data' => [
                    'cache_driver' => config('cache.default'),
                    'session_driver' => config('session.driver'),
                    'queue_driver' => config('queue.default')
                ]
            ]);
        })->name('cache-info');
    });
}); // Closing brace for auth:sanctum middleware

// =============================================
// 🚫 DEBUG ROUTES (Development Only)
// =============================================
if (config('app.debug') && config('app.env') !== 'production') {
    Route::middleware('api.auth:sanctum')->prefix('debug')->name('api.debug.')->group(function () {
        Route::get('/user', function (Request $request) {
            return response()->json([
                'success' => true,
                'user' => $request->user()->only(['id', 'name', 'email']),
                'token' => $request->bearerToken() ? 'Present' : 'Missing',
                'sanctum_user' => $request->user() ? 'Authenticated' : 'Not authenticated',
                'guards' => array_keys(config('auth.guards')),
                'current_guard' => 'sanctum',
                'cache_driver' => config('cache.default'),
                'session_driver' => config('session.driver')
            ]);
        })->name('user');
        
        // NEW: Workout templates debug route
        Route::get('/workouts', [WorkoutController::class, 'debugTemplates'])->name('workouts');
        
        // NEW: Force reseed templates for current user
        Route::post('/workouts/reseed', [WorkoutController::class, 'reseedUserTemplates'])->name('workouts.reseed');

        // NEW: Seed exercises (development only)
        Route::post('/seed-exercises', function () {
            try {
                \Artisan::call('db:seed', ['--class' => 'ExerciseSeeder']);
                $exerciseCount = \App\Models\Exercise::count();
                return response()->json([
                    'success' => true,
                    'message' => 'Exercise seeder completed successfully',
                    'exercises_created' => $exerciseCount,
                    'output' => \Artisan::output()
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to run exercise seeder: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
        })->name('seed.exercises');
        
        // NEW: Clear and reseed exercises (development only)
        Route::post('/clear-exercises', function () {
            try {
                \App\Models\Exercise::truncate();
                return response()->json([
                    'success' => true,
                    'message' => 'All exercises cleared successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to clear exercises: ' . $e->getMessage()
                ], 500);
            }
        })->name('clear.exercises');

        // NEW: Database status check
        Route::get('/database', function () {
            try {
                $users = \App\Models\User::count();
                $exercises = \App\Models\Exercise::count();
                $workouts = \App\Models\Workout::count();
                $templates = \App\Models\Workout::where('is_template', true)->count();
                $sessions = \App\Models\Workout::where('is_template', false)->count();
                $workoutExercises = \App\Models\WorkoutExercise::count();
                
                // Check for foreign key constraints
                $orphanedWorkouts = \App\Models\Workout::whereNotExists(function($query) {
                    $query->select(\DB::raw(1))
                          ->from('users')
                          ->whereRaw('users.id = workouts.user_id');
                })->count();
                
                $orphanedWorkoutExercises = \App\Models\WorkoutExercise::whereNotExists(function($query) {
                    $query->select(\DB::raw(1))
                          ->from('workouts')
                          ->whereRaw('workouts.id = workout_exercises.workout_id');
                })->count();
                
                return response()->json([
                    'success' => true,
                    'database_status' => [
                        'users' => $users,
                        'exercises' => $exercises,
                        'workouts_total' => $workouts,
                        'workout_templates' => $templates,
                        'workout_sessions' => $sessions,
                        'workout_exercises_pivot' => $workoutExercises,
                        'orphaned_workouts' => $orphanedWorkouts,
                        'orphaned_workout_exercises' => $orphanedWorkoutExercises
                    ]
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Database check failed: ' . $e->getMessage()
                ], 500);
            }
        })->name('database');
        
        Route::get('/routes', function () {
            $routes = collect(\Route::getRoutes())->map(function ($route) {
                return [
                    'method' => implode('|', $route->methods()),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'middleware' => $route->gatherMiddleware()
                ];
            })->filter(function ($route) {
                return strpos($route['uri'], 'api/') === 0;
            })->values();
            
            return response()->json([
                'success' => true,
                'routes' => $routes,
                'total' => $routes->count()
            ]);
        })->name('routes');
    });
}

// =============================================
// 🚫 FALLBACK ROUTE
// =============================================
Route::fallback(function (Request $request) {
    $method = $request->method();
    $path = $request->path();
    
    // Smart suggestions based on requested path
    $suggestions = [];
    
    $pathSegments = explode('/', $path);
    $firstSegment = $pathSegments[1] ?? '';
    
    switch ($firstSegment) {
        case 'exercise':
        case 'exercises':
            $suggestions = [
                'GET /api/exercises - List all exercises',
                'GET /api/exercises/{id} - Get specific exercise',
                'GET /api/exercises/search?q=term - Search exercises'
            ];
            break;
            
        case 'workout':
        case 'workouts':
            $suggestions = [
                'GET /api/workouts/plans - Get workout plans (auth required)',
                'GET /api/workouts/sessions - Get workout sessions (auth required)',
                'GET /api/workouts/stats - Get workout statistics (auth required)'
            ];
            break;
            
        case 'auth':
        case 'login':
        case 'register':
            $suggestions = [
                'POST /api/auth/login - User login',
                'POST /api/auth/register - User registration',
                'GET /api/auth/me - Get current user (authenticated)',
                'POST /api/auth/logout - User logout (authenticated)'
            ];
            break;
            
        case 'dashboard':
            $suggestions = [
                'GET /api/dashboard - Get dashboard data (authenticated)',
                'GET /api/dashboard/stats - Get dashboard statistics (authenticated)',
                'GET /api/dashboard/performance - Get performance data (authenticated)'
            ];
            break;
            
        case 'calendar':
            $suggestions = [
                'GET /api/calendar/tasks - Get calendar tasks (authenticated)',
                'GET /api/calendar/today - Get today\'s tasks (authenticated)',
                'GET /api/calendar/week - Get this week\'s tasks (authenticated)',
                'GET /api/calendar/month/{month} - Get monthly tasks (authenticated)',
                'GET /api/calendar/tasks/month/{month} - Alternative monthly tasks (authenticated)'
            ];
            break;
            
        default:
            $suggestions = [
                'GET /api/test - Test API connectivity',
                'GET /api/health - Check API health',
                'GET /api/exercises - Browse exercises',
                'POST /api/auth/login - User authentication',
                'GET /api/dashboard - User dashboard (authenticated)'
            ];
    }
    
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error' => [
            'requested_method' => $method,
            'requested_path' => $path,
            'available_suggestions' => $suggestions
        ],
        'documentation' => [
            'base_url' => config('app.url') . '/api',
            'authentication' => 'Bearer Token (Laravel Sanctum)',
            'content_type' => 'application/json',
            'version' => '2.0.0'
        ]
    ], 404);
})->name('api.fallback');