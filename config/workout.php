<?php
// config/workout.php - Configuration centralisée de l'application
return [
    /*
    |--------------------------------------------------------------------------
    | Workout Application Configuration
    |--------------------------------------------------------------------------
    */

    'app_name' => env('WORKOUT_APP_NAME', 'FitnessPro'),
    'version' => '2.0.0',

    /*
    |--------------------------------------------------------------------------
    | Features Toggles
    |--------------------------------------------------------------------------
    */
    'features' => [
        'achievements' => env('WORKOUT_ACHIEVEMENTS_ENABLED', true),
        'notifications' => env('WORKOUT_NOTIFICATIONS_ENABLED', true),
        'analytics' => env('WORKOUT_ANALYTICS_ENABLED', true),
        'social_sharing' => env('WORKOUT_SOCIAL_ENABLED', false),
        'ai_recommendations' => env('WORKOUT_AI_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'api_default' => [
            'max_attempts' => env('WORKOUT_RATE_LIMIT_MAX', 60),
            'decay_minutes' => env('WORKOUT_RATE_LIMIT_DECAY', 1),
        ],
        'session_creation' => [
            'max_attempts' => 30,
            'decay_minutes' => 1,
        ],
        'plan_creation' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
        'statistics' => [
            'max_attempts' => 120,
            'decay_minutes' => 1,
        ],
        'export' => [
            'max_attempts' => 5,
            'decay_minutes' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Calorie Calculation (MET Values)
    |--------------------------------------------------------------------------
    */
    'calorie_rates' => [
        'general' => 6.0,
        'cardio' => 8.0,
        'strength' => 6.0,
        'yoga' => 3.0,
        'hiit' => 10.0,
        'running' => 12.0,
        'cycling' => 8.5,
        'swimming' => 11.0,
        'walking' => 4.0,
        'core' => 5.0,
        'chest' => 6.0,
        'back' => 6.0,
        'legs' => 8.0,
        'shoulders' => 5.0,
        'arms' => 5.0,
        'abs' => 4.0,
        'mobility' => 3.0,
        'flexibility' => 3.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Workout Categories
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'strength' => [
            'label' => 'Musculation',
            'icon' => 'fas fa-dumbbell',
            'color' => '#e74c3c',
            'met_value' => 6.0,
        ],
        'cardio' => [
            'label' => 'Cardio',
            'icon' => 'fas fa-heart',
            'color' => '#e67e22',
            'met_value' => 8.0,
        ],
        'flexibility' => [
            'label' => 'Flexibilité',
            'icon' => 'fas fa-leaf',
            'color' => '#27ae60',
            'met_value' => 3.0,
        ],
        'hiit' => [
            'label' => 'HIIT',
            'icon' => 'fas fa-fire',
            'color' => '#f39c12',
            'met_value' => 10.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Difficulty Levels
    |--------------------------------------------------------------------------
    */
    'difficulty_levels' => [
        'beginner' => [
            'label' => 'Débutant',
            'color' => '#27ae60',
            'multiplier' => 0.8,
        ],
        'intermediate' => [
            'label' => 'Intermédiaire',
            'color' => '#f39c12',
            'multiplier' => 1.0,
        ],
        'advanced' => [
            'label' => 'Avancé',
            'color' => '#e74c3c',
            'multiplier' => 1.3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Achievement System
    |--------------------------------------------------------------------------
    */
    'achievements' => [
        'session_milestones' => [1, 5, 10, 25, 50, 100, 250, 500, 1000],
        'streak_milestones' => [3, 7, 14, 30, 60, 100, 365],
        'calorie_milestones' => [1000, 5000, 10000, 25000, 50000, 100000],
        'time_milestones' => [10, 25, 50, 100, 250, 500, 1000], // in hours
        
        'levels' => [
            'copper' => ['min' => 0, 'color' => '#cd7f32'],
            'bronze' => ['min' => 10, 'color' => '#cd7f32'],
            'silver' => ['min' => 25, 'color' => '#c0c0c0'],
            'gold' => ['min' => 50, 'color' => '#ffd700'],
            'epic' => ['min' => 100, 'color' => '#9932cc'],
            'legendary' => ['min' => 500, 'color' => '#ff4500'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Motivational Quotes
    |--------------------------------------------------------------------------
    */
    'motivational_quotes' => [
        "Le succès n'est pas final, l'échec n'est pas fatal : c'est le courage de continuer qui compte.",
        "Votre corps peut le faire. C'est votre esprit qu'il faut convaincre.",
        "Les champions continuent à jouer jusqu'à ce qu'ils gagnent.",
        "La discipline est le pont entre les objectifs et les réalisations.",
        "Ne limitez pas vos défis, défiez vos limites.",
        "Chaque expert était autrefois un débutant.",
        "La motivation vous fait commencer, l'habitude vous fait continuer.",
        "La seule mauvaise séance d'entraînement est celle que vous n'avez pas faite.",
        "Votre santé est un investissement, pas une dépense.",
        "Transformez votre 'je ne peux pas' en 'je vais essayer'.",
        "Le corps réalise ce que l'esprit croit.",
        "Chaque jour est une chance de devenir plus fort.",
        "L'excellence n'est pas un acte, mais une habitude.",
        "Votre seule limite est vous-même.",
        "Commencez là où vous êtes, utilisez ce que vous avez, faites ce que vous pouvez.",
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'statistics_ttl' => env('WORKOUT_CACHE_STATS_TTL', 3600), // 1 hour
        'user_stats_ttl' => env('WORKOUT_CACHE_USER_TTL', 1800), // 30 minutes
        'achievements_ttl' => env('WORKOUT_CACHE_ACHIEVEMENTS_TTL', 7200), // 2 hours
        'recommendations_ttl' => env('WORKOUT_CACHE_RECOMMENDATIONS_TTL', 14400), // 4 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Limits
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_limit' => 20,
        'max_limit' => 100,
        'sessions_limit' => 50,
        'plans_limit' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Limits
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_file_size' => env('WORKOUT_MAX_FILE_SIZE', 10485760), // 10MB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_video_types' => ['mp4', 'webm', 'mov'],
    ],

    /*
    |--------------------------------------------------------------------------
    | BMI Configuration
    |--------------------------------------------------------------------------
    */
    'bmi' => [
        'ranges' => [
            'severe_underweight' => ['min' => 0, 'max' => 16, 'label' => 'Sous-poids sévère', 'color' => '#1e40af'],
            'moderate_underweight' => ['min' => 16, 'max' => 17, 'label' => 'Sous-poids modéré', 'color' => '#2563eb'],
            'mild_underweight' => ['min' => 17, 'max' => 18.5, 'label' => 'Sous-poids léger', 'color' => '#3b82f6'],
            'normal' => ['min' => 18.5, 'max' => 25, 'label' => 'Poids normal', 'color' => '#21BF73'],
            'overweight' => ['min' => 25, 'max' => 30, 'label' => 'Surpoids', 'color' => '#f59e0b'],
            'obese_class_1' => ['min' => 30, 'max' => 35, 'label' => 'Obésité classe I', 'color' => '#ef4444'],
            'obese_class_2' => ['min' => 35, 'max' => 40, 'label' => 'Obésité classe II', 'color' => '#dc2626'],
            'obese_class_3' => ['min' => 40, 'max' => 100, 'label' => 'Obésité classe III', 'color' => '#b91c1c'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'channels' => [
            'database' => env('WORKOUT_NOTIFICATIONS_DB', true),
            'mail' => env('WORKOUT_NOTIFICATIONS_MAIL', false),
            'push' => env('WORKOUT_NOTIFICATIONS_PUSH', false),
            'sms' => env('WORKOUT_NOTIFICATIONS_SMS', false),
        ],
        'achievement_delay' => 5, // seconds
        'reminder_time' => '09:00', // Default reminder time
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'track_api_calls' => env('WORKOUT_TRACK_API_CALLS', true),
        'track_user_behavior' => env('WORKOUT_TRACK_BEHAVIOR', true),
        'retention_days' => env('WORKOUT_ANALYTICS_RETENTION', 90),
        'aggregation_intervals' => ['hourly', 'daily', 'weekly', 'monthly'],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI and Machine Learning
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'recommendation_engine' => env('WORKOUT_AI_RECOMMENDATIONS', false),
        'difficulty_adjustment' => env('WORKOUT_AI_DIFFICULTY', false),
        'rest_time_optimization' => env('WORKOUT_AI_REST_TIME', false),
        'injury_prevention' => env('WORKOUT_AI_INJURY_PREVENTION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Features
    |--------------------------------------------------------------------------
    */
    'social' => [
        'enable_sharing' => env('WORKOUT_SOCIAL_SHARING', false),
        'enable_challenges' => env('WORKOUT_SOCIAL_CHALLENGES', false),
        'enable_leaderboards' => env('WORKOUT_SOCIAL_LEADERBOARDS', false),
        'privacy_default' => 'friends', // public, friends, private
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'fitness_trackers' => [
            'fitbit' => env('WORKOUT_FITBIT_ENABLED', false),
            'garmin' => env('WORKOUT_GARMIN_ENABLED', false),
            'apple_health' => env('WORKOUT_APPLE_HEALTH_ENABLED', false),
            'google_fit' => env('WORKOUT_GOOGLE_FIT_ENABLED', false),
        ],
        'nutrition_apps' => [
            'myfitnesspal' => env('WORKOUT_MFP_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'enable_query_logging' => env('WORKOUT_LOG_QUERIES', false),
        'slow_query_threshold' => env('WORKOUT_SLOW_QUERY_MS', 1000),
        'enable_profiling' => env('WORKOUT_ENABLE_PROFILING', false),
        'cache_driver' => env('WORKOUT_CACHE_DRIVER', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'enable_2fa' => env('WORKOUT_2FA_ENABLED', false),
        'session_timeout' => env('WORKOUT_SESSION_TIMEOUT', 120), // minutes
        'max_login_attempts' => env('WORKOUT_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('WORKOUT_LOCKOUT_DURATION', 15), // minutes
    ],
];
