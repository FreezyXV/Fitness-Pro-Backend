<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workout;
use App\Models\User;
use App\Models\Exercise;
use Illuminate\Support\Collection;

class WorkoutPlansSeeder extends Seeder
{
    private Collection $exercises;
    private ?User $user = null;

    public function run(): void
    {
        echo "🏃‍♂️ Creating intelligent workout templates...\n";

        // MODIFIÉ: Utiliser votre email ou créer un utilisateur unique
        $this->user = User::where('email', 'i_841@mail.ru')->first();
        
        if (!$this->user) {
            // Si votre utilisateur n'existe pas, utiliser le premier disponible
            $this->user = User::first();
            
            if (!$this->user) {
                // Créer un utilisateur si aucun n'existe
                $this->user = User::create([
                    'name' => 'Ivan Petrov',
                    'email' => 'i_841@mail.ru',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]);
                echo "✅ User created: {$this->user->email}\n";
            } else {
                echo "✅ Using existing user: {$this->user->email} (ID: {$this->user->id})\n";
            }
        } else {
            echo "✅ Using your user: {$this->user->email} (ID: {$this->user->id})\n";
        }

        // Supprimer les templates existants pour cet utilisateur pour éviter les doublons
        $existingCount = Workout::where('user_id', $this->user->id)->where('is_template', true)->count();
        if ($existingCount > 0) {
            echo "🗑️ Removing {$existingCount} existing templates for this user...\n";
            Workout::where('user_id', $this->user->id)->where('is_template', true)->delete();
        }

        // Load all exercises
        $this->exercises = Exercise::all()->keyBy('name');
        
        if ($this->exercises->isEmpty()) {
            echo "⚠️  No exercises found in database. Please run ExerciseSeeder first.\n";
            echo "Run: php artisan db:seed --class=ExerciseSeeder\n";
            return;
        }
        
        echo "✅ Loaded {$this->exercises->count()} exercises from database\n";

        // Create workout plans
        $this->createBeginnerPlans();
        $this->createIntermediatePlans();
        $this->createAdvancedPlans();
        $this->createWeightLossPlans();
        $this->createSpecialtyPlans();

        echo "\n✅ All workout templates created successfully for {$this->user->name}!\n";
        $this->displayStatistics();
    }

    private function createBeginnerPlans(): void
    {
        echo "\n📚 Creating Beginner Plans...\n";

        // Beginner Full Body Starter
        $this->createWorkout([
            'name' => 'Programme Débutant - Corps Entier',
            'description' => 'Introduction parfaite au fitness avec des mouvements de base pour tout le corps',
            'category' => 'strength',
            'difficulty_level' => 'beginner',
            'exercises' => [
                // Warm-up
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                
                // Main workout
                ['name' => 'Push-Up', 'sets' => 2, 'reps' => 5, 'restTime' => 60],
                ['name' => 'Squat', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Superman', 'sets' => 2, 'reps' => 6, 'restTime' => 60],
                ['name' => 'Glute Bridge', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Crunch', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                
                // Cool-down
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 90, 'restTime' => 0],
            ]
        ]);

        // Beginner Upper Body Focus
        $this->createWorkout([
            'name' => 'Débutant - Haut du Corps',
            'description' => 'Renforcement progressif des bras, épaules et poitrine',
            'category' => 'strength',
            'difficulty_level' => 'beginner',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 45, 'restTime' => 0],
                ['name' => 'Push-Up', 'sets' => 3, 'reps' => 4, 'restTime' => 90],
                ['name' => 'Superman', 'sets' => 3, 'reps' => 6, 'restTime' => 60],
                ['name' => 'Standing Dumbbell Shrug', 'sets' => 2, 'reps' => 10, 'restTime' => 60],
                ['name' => 'Seated Concentration Curl', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 2, 'duration' => 15, 'restTime' => 60],
            ]
        ]);

        // Beginner Lower Body Focus
        $this->createWorkout([
            'name' => 'Débutant - Bas du Corps',
            'description' => 'Développement des jambes et fessiers avec des mouvements sûrs',
            'category' => 'strength',
            'difficulty_level' => 'beginner',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                ['name' => 'Squat', 'sets' => 3, 'reps' => 10, 'restTime' => 90],
                ['name' => 'Glute Bridge', 'sets' => 3, 'reps' => 12, 'restTime' => 60],
                ['name' => 'Donkey Kicks', 'sets' => 2, 'reps' => 8, 'restTime' => 45],
                ['name' => 'Forward Lunge', 'sets' => 2, 'reps' => 6, 'restTime' => 75],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 90, 'restTime' => 0],
            ]
        ]);

        // Beginner Cardio & Mobility
        $this->createWorkout([
            'name' => 'Débutant - Cardio & Mobilité',
            'description' => 'Amélioration de l\'endurance et de la flexibilité',
            'category' => 'cardio',
            'difficulty_level' => 'beginner',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 2, 'duration' => 45, 'restTime' => 30],
                ['name' => 'Inchworm', 'sets' => 2, 'reps' => 5, 'restTime' => 60],
                ['name' => 'Rotating Mountain Climber', 'sets' => 3, 'duration' => 20, 'restTime' => 60],
                ['name' => 'Alternate Heel Touches', 'sets' => 2, 'reps' => 12, 'restTime' => 45],
                ['name' => 'Dynamic Pigeon', 'sets' => 2, 'duration' => 60, 'restTime' => 30],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);
    }

    private function createIntermediatePlans(): void
    {
        echo "\n💪 Creating Intermediate Plans...\n";

        // Upper/Lower Split - Upper
        $this->createWorkout([
            'name' => 'Intermédiaire - Upper Body',
            'description' => 'Entraînement intensif du haut du corps avec progressions',
            'category' => 'strength',
            'difficulty_level' => 'intermediate',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                ['name' => 'Push-Up Wide Grip', 'sets' => 3, 'reps' => 8, 'restTime' => 90],
                ['name' => 'Pull-Up', 'sets' => 3, 'reps' => 5, 'restTime' => 120],
                ['name' => 'Chin-Up', 'sets' => 2, 'reps' => 6, 'restTime' => 120],
                ['name' => 'Seated Overhead Dumbbell Tricep Extension', 'sets' => 3, 'reps' => 10, 'restTime' => 75],
                ['name' => 'Seated Concentration Curl', 'sets' => 3, 'reps' => 10, 'restTime' => 75],
                ['name' => 'Kettlebell Halo', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 90, 'restTime' => 0],
            ]
        ]);

        // Upper/Lower Split - Lower
        $this->createWorkout([
            'name' => 'Intermédiaire - Lower Body',
            'description' => 'Développement avancé des jambes et du tronc',
            'category' => 'strength',
            'difficulty_level' => 'intermediate',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 2, 'duration' => 45, 'restTime' => 30],
                ['name' => 'Squat', 'sets' => 4, 'reps' => 12, 'restTime' => 90],
                ['name' => 'Forward Lunge', 'sets' => 3, 'reps' => 10, 'restTime' => 90],
                ['name' => 'Alternating Lateral Lunge', 'sets' => 3, 'reps' => 12, 'restTime' => 90],
                ['name' => 'Fire Hydrant Circles', 'sets' => 3, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Glute Bridge', 'sets' => 3, 'reps' => 15, 'restTime' => 60],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);

        // Intermediate Full Body
        $this->createWorkout([
            'name' => 'Intermédiaire - Circuit Corps Entier',
            'description' => 'Entraînement en circuit pour force et endurance',
            'category' => 'strength',
            'difficulty_level' => 'intermediate',
            'exercises' => [
                ['name' => 'Inchworm', 'sets' => 2, 'reps' => 6, 'restTime' => 45],
                ['name' => 'Push-Up Wide Grip', 'sets' => 3, 'reps' => 10, 'restTime' => 60],
                ['name' => 'Squat', 'sets' => 3, 'reps' => 15, 'restTime' => 60],
                ['name' => 'Superman', 'sets' => 3, 'reps' => 12, 'restTime' => 60],
                ['name' => 'Forward Lunge', 'sets' => 3, 'reps' => 12, 'restTime' => 60],
                ['name' => 'Bicycle Crunch', 'sets' => 3, 'reps' => 20, 'restTime' => 60],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 2, 'duration' => 30, 'restTime' => 75],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 90, 'restTime' => 0],
            ]
        ]);

        // Intermediate HIIT
        $this->createWorkout([
            'name' => 'Intermédiaire - HIIT Intense',
            'description' => 'Entraînement haute intensité pour brûler les calories',
            'category' => 'cardio',
            'difficulty_level' => 'intermediate',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                ['name' => 'Inchworm', 'sets' => 3, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Rotating Mountain Climber', 'sets' => 4, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Push-Up', 'sets' => 4, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Squat', 'sets' => 4, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Bicycle Crunch', 'sets' => 3, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);

        // Core Focus
        $this->createWorkout([
            'name' => 'Intermédiaire - Spécial Abdos',
            'description' => 'Renforcement complet du tronc et des abdominaux',
            'category' => 'strength',
            'difficulty_level' => 'intermediate',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 45, 'restTime' => 0],
                ['name' => 'Crunch', 'sets' => 3, 'reps' => 15, 'restTime' => 60],
                ['name' => 'Oblique Crunch', 'sets' => 3, 'reps' => 12, 'restTime' => 60],
                ['name' => 'Sit-Up', 'sets' => 3, 'reps' => 12, 'restTime' => 75],
                ['name' => 'Bicycle Crunch', 'sets' => 3, 'reps' => 20, 'restTime' => 60],
                ['name' => 'Straight Leg Toe Touch', 'sets' => 3, 'reps' => 15, 'restTime' => 60],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 3, 'duration' => 30, 'restTime' => 75],
                ['name' => 'Alternate Heel Touches', 'sets' => 2, 'reps' => 20, 'restTime' => 45],
            ]
        ]);
    }

    private function createAdvancedPlans(): void
    {
        echo "\n🔥 Creating Advanced Plans...\n";

        // Advanced Push/Pull/Legs - Push
        $this->createWorkout([
            'name' => 'Avancé - Push (Poussée)',
            'description' => 'Entraînement avancé pour poitrine, épaules et triceps',
            'category' => 'strength',
            'difficulty_level' => 'advanced',
            'exercises' => [
                ['name' => 'Kettlebell Halo', 'sets' => 2, 'reps' => 10, 'restTime' => 60],
                ['name' => 'Spider-Man Push-Up', 'sets' => 4, 'reps' => 8, 'restTime' => 120],
                ['name' => 'Push-Up Wide Grip', 'sets' => 4, 'reps' => 12, 'restTime' => 90],
                ['name' => 'Push-Up', 'sets' => 3, 'reps' => 15, 'restTime' => 90],
                ['name' => 'Seated Overhead Dumbbell Tricep Extension', 'sets' => 4, 'reps' => 12, 'restTime' => 90],
                ['name' => 'Standing Dumbbell Shrug', 'sets' => 3, 'reps' => 15, 'restTime' => 75],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);

        // Advanced Push/Pull/Legs - Pull
        $this->createWorkout([
            'name' => 'Avancé - Pull (Traction)',
            'description' => 'Développement maximal du dos et des biceps',
            'category' => 'strength',
            'difficulty_level' => 'advanced',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 2, 'duration' => 45, 'restTime' => 30],
                ['name' => 'Pull-Up Wide Grip', 'sets' => 4, 'reps' => 6, 'restTime' => 150],
                ['name' => 'Pull-Up', 'sets' => 4, 'reps' => 8, 'restTime' => 135],
                ['name' => 'Chin-Up', 'sets' => 3, 'reps' => 10, 'restTime' => 120],
                ['name' => 'Superman', 'sets' => 4, 'reps' => 15, 'restTime' => 75],
                ['name' => 'Seated Concentration Curl', 'sets' => 4, 'reps' => 12, 'restTime' => 90],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);

        // Advanced Push/Pull/Legs - Legs
        $this->createWorkout([
            'name' => 'Avancé - Legs (Jambes)',
            'description' => 'Entraînement intense pour jambes et fessiers',
            'category' => 'strength',
            'difficulty_level' => 'advanced',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 2, 'duration' => 60, 'restTime' => 30],
                ['name' => 'Double Kettlebell Front Squat', 'sets' => 4, 'reps' => 10, 'restTime' => 135],
                ['name' => 'Squat', 'sets' => 4, 'reps' => 20, 'restTime' => 120],
                ['name' => 'Forward Lunge', 'sets' => 4, 'reps' => 15, 'restTime' => 105],
                ['name' => 'Alternating Lateral Lunge', 'sets' => 3, 'reps' => 16, 'restTime' => 90],
                ['name' => 'Fire Hydrant Circles', 'sets' => 3, 'reps' => 12, 'restTime' => 75],
                ['name' => 'Glute Bridge', 'sets' => 4, 'reps' => 20, 'restTime' => 75],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 150, 'restTime' => 0],
            ]
        ]);

        // Advanced Full Body Beast Mode
        $this->createWorkout([
            'name' => 'Avancé - Beast Mode',
            'description' => 'Entraînement ultime pour athlètes confirmés',
            'category' => 'strength',
            'difficulty_level' => 'advanced',
            'exercises' => [
                ['name' => 'Inchworm', 'sets' => 3, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Spider-Man Push-Up', 'sets' => 4, 'reps' => 10, 'restTime' => 90],
                ['name' => 'Pull-Up Wide Grip', 'sets' => 4, 'reps' => 6, 'restTime' => 120],
                ['name' => 'Double Kettlebell Front Squat', 'sets' => 4, 'reps' => 12, 'restTime' => 120],
                ['name' => 'Rotating Mountain Climber', 'sets' => 4, 'duration' => 45, 'restTime' => 75],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 3, 'duration' => 45, 'restTime' => 90],
                ['name' => 'Bicycle Crunch', 'sets' => 3, 'reps' => 30, 'restTime' => 60],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 180, 'restTime' => 0],
            ]
        ]);

        // Advanced HIIT Extreme
        $this->createWorkout([
            'name' => 'Avancé - HIIT Extrême',
            'description' => 'Cardio haute intensité pour condition physique ultime',
            'category' => 'cardio',
            'difficulty_level' => 'advanced',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                ['name' => 'Inchworm', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Spider-Man Push-Up', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Rotating Mountain Climber', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Squat', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Bicycle Crunch', 'sets' => 4, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 180, 'restTime' => 0],
            ]
        ]);
    }

    private function createWeightLossPlans(): void
    {
        echo "\n🔥 Creating Weight Loss Plans...\n";

        // Weight Loss - Light Version (Beginner friendly)
        $this->createWorkout([
            'name' => 'Perte de Poids - Version Légère',
            'description' => 'Programme doux pour débuter la perte de poids avec des mouvements accessibles',
            'category' => 'hiit',
            'difficulty_level' => 'beginner',
            'is_public' => true,
            'exercises' => [
                // Warm-up with calorie burn
                ['name' => 'Hip Circles', 'sets' => 2, 'duration' => 60, 'restTime' => 20],
                ['name' => 'Inchworm', 'sets' => 2, 'reps' => 4, 'restTime' => 30],
                
                // Main fat burning circuit
                ['name' => 'Squat', 'sets' => 3, 'reps' => 15, 'restTime' => 45],
                ['name' => 'Push-Up', 'sets' => 3, 'reps' => 6, 'restTime' => 45],
                ['name' => 'Rotating Mountain Climber', 'sets' => 3, 'duration' => 20, 'restTime' => 45],
                ['name' => 'Glute Bridge', 'sets' => 3, 'reps' => 15, 'restTime' => 45],
                ['name' => 'Crunch', 'sets' => 3, 'reps' => 12, 'restTime' => 45],
                ['name' => 'Alternate Heel Touches', 'sets' => 2, 'reps' => 16, 'restTime' => 30],
                
                // Active recovery
                ['name' => 'Dynamic Pigeon', 'sets' => 2, 'duration' => 60, 'restTime' => 30],
            ]
        ]);

        // Weight Loss - Regular Mode
        $this->createWorkout([
            'name' => 'Perte de Poids - Mode Régulier',
            'description' => 'Entraînement équilibré pour une perte de poids progressive et durable',
            'category' => 'hiit',
            'difficulty_level' => 'intermediate',
            'is_public' => true,
            'exercises' => [
                // Dynamic warm-up
                ['name' => 'Hip Circles', 'sets' => 2, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Inchworm', 'sets' => 3, 'reps' => 6, 'restTime' => 30],
                
                // Circuit 1: Lower body focus
                ['name' => 'Squat', 'sets' => 4, 'reps' => 18, 'restTime' => 30],
                ['name' => 'Forward Lunge', 'sets' => 3, 'reps' => 14, 'restTime' => 30],
                ['name' => 'Alternating Lateral Lunge', 'sets' => 3, 'reps' => 16, 'restTime' => 30],
                
                // Circuit 2: Upper body + core
                ['name' => 'Push-Up Wide Grip', 'sets' => 4, 'reps' => 10, 'restTime' => 30],
                ['name' => 'Rotating Mountain Climber', 'sets' => 4, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Bicycle Crunch', 'sets' => 4, 'reps' => 24, 'restTime' => 30],
                
                // Circuit 3: Full body burn
                ['name' => 'Glute Bridge', 'sets' => 3, 'reps' => 20, 'restTime' => 30],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 3, 'duration' => 25, 'restTime' => 45],
                ['name' => 'Straight Leg Toe Touch', 'sets' => 3, 'reps' => 16, 'restTime' => 30],
                
                // Cool-down stretch
                ['name' => 'Dynamic Pigeon', 'sets' => 2, 'duration' => 90, 'restTime' => 30],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 90, 'restTime' => 0],
            ]
        ]);

        // Weight Loss - Intensive Mode
        $this->createWorkout([
            'name' => 'Perte de Poids - Mode Intensif',
            'description' => 'Programme haute intensité pour maximiser la combustion des graisses',
            'category' => 'hiit',
            'difficulty_level' => 'advanced',
            'is_public' => true,
            'exercises' => [
                // High-intensity warm-up
                ['name' => 'Hip Circles', 'sets' => 2, 'duration' => 45, 'restTime' => 10],
                ['name' => 'Inchworm', 'sets' => 3, 'reps' => 8, 'restTime' => 20],
                
                // HIIT Circuit 1 (45s work, 15s rest)
                ['name' => 'Squat', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Spider-Man Push-Up', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Rotating Mountain Climber', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Forward Lunge', 'sets' => 4, 'duration' => 45, 'restTime' => 15],
                
                // HIIT Circuit 2 (40s work, 20s rest)
                ['name' => 'Push-Up Wide Grip', 'sets' => 4, 'duration' => 40, 'restTime' => 20],
                ['name' => 'Alternating Lateral Lunge', 'sets' => 4, 'duration' => 40, 'restTime' => 20],
                ['name' => 'Bicycle Crunch', 'sets' => 4, 'duration' => 40, 'restTime' => 20],
                ['name' => 'Fire Hydrant Circles', 'sets' => 3, 'duration' => 40, 'restTime' => 20],
                
                // Metabolic Finisher (30s work, 10s rest)
                ['name' => 'Glute Bridge', 'sets' => 4, 'duration' => 30, 'restTime' => 10],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 4, 'duration' => 30, 'restTime' => 10],
                ['name' => 'Sit-Up', 'sets' => 3, 'duration' => 30, 'restTime' => 10],
                
                // Extended cool-down for recovery
                ['name' => 'Dynamic Pigeon', 'sets' => 2, 'duration' => 120, 'restTime' => 45],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);

        // Weight Loss - HIIT Fat Burner
        $this->createWorkout([
            'name' => 'Perte de Poids - HIIT Brûleur',
            'description' => 'Session HIIT courte mais explosive pour brûler un maximum de calories',
            'category' => 'hiit',
            'difficulty_level' => 'intermediate',
            'is_public' => true,
            'exercises' => [
                // Quick activation
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 30, 'restTime' => 0],
                ['name' => 'Inchworm', 'sets' => 2, 'reps' => 5, 'restTime' => 20],
                
                // Tabata Style (20s work, 10s rest) x 4 rounds
                ['name' => 'Squat', 'sets' => 8, 'duration' => 20, 'restTime' => 10],
                ['name' => 'Push-Up', 'sets' => 8, 'duration' => 20, 'restTime' => 10],
                ['name' => 'Rotating Mountain Climber', 'sets' => 8, 'duration' => 20, 'restTime' => 10],
                ['name' => 'Bicycle Crunch', 'sets' => 6, 'duration' => 20, 'restTime' => 10],
                
                // Recovery stretch
                ['name' => 'Dynamic Pigeon', 'sets' => 2, 'duration' => 60, 'restTime' => 30],
            ]
        ]);

        // Weight Loss - Circuit Training
        $this->createWorkout([
            'name' => 'Perte de Poids - Circuit Training',
            'description' => 'Circuit complet alternant force et cardio pour optimiser la dépense calorique',
            'category' => 'hiit',
            'difficulty_level' => 'intermediate',
            'is_public' => true,
            'exercises' => [
                // Movement preparation
                ['name' => 'Hip Circles', 'sets' => 2, 'duration' => 45, 'restTime' => 15],
                
                // Circuit 1: Compound movements
                ['name' => 'Squat', 'sets' => 3, 'reps' => 20, 'restTime' => 20],
                ['name' => 'Push-Up Wide Grip', 'sets' => 3, 'reps' => 12, 'restTime' => 20],
                ['name' => 'Forward Lunge', 'sets' => 3, 'reps' => 16, 'restTime' => 20],
                ['name' => 'Superman', 'sets' => 3, 'reps' => 15, 'restTime' => 60], // Rest between circuits
                
                // Circuit 2: Cardio intensive
                ['name' => 'Inchworm', 'sets' => 3, 'reps' => 8, 'restTime' => 20],
                ['name' => 'Rotating Mountain Climber', 'sets' => 3, 'duration' => 40, 'restTime' => 20],
                ['name' => 'Alternating Lateral Lunge', 'sets' => 3, 'reps' => 18, 'restTime' => 20],
                ['name' => 'Bicycle Crunch', 'sets' => 3, 'reps' => 25, 'restTime' => 60],
                
                // Circuit 3: Core & stability
                ['name' => 'Glute Bridge', 'sets' => 3, 'reps' => 22, 'restTime' => 20],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 3, 'duration' => 30, 'restTime' => 20],
                ['name' => 'Straight Leg Toe Touch', 'sets' => 3, 'reps' => 18, 'restTime' => 20],
                
                // Final stretch
                ['name' => 'Dynamic Pigeon', 'sets' => 2, 'duration' => 90, 'restTime' => 0],
            ]
        ]);

        // Weight Loss - Beginner Cardio
        $this->createWorkout([
            'name' => 'Perte de Poids - Cardio Débutant',
            'description' => 'Introduction au cardio pour brûler les graisses en douceur',
            'category' => 'hiit',
            'difficulty_level' => 'beginner',
            'is_public' => true,
            'exercises' => [
                // Gentle warm-up
                ['name' => 'Hip Circles', 'sets' => 3, 'duration' => 60, 'restTime' => 30],
                
                // Low-impact cardio
                ['name' => 'Squat', 'sets' => 4, 'reps' => 12, 'restTime' => 60],
                ['name' => 'Glute Bridge', 'sets' => 4, 'reps' => 15, 'restTime' => 45],
                ['name' => 'Crunch', 'sets' => 3, 'reps' => 12, 'restTime' => 45],
                ['name' => 'Alternate Heel Touches', 'sets' => 3, 'reps' => 20, 'restTime' => 45],
                ['name' => 'Donkey Kicks', 'sets' => 3, 'reps' => 12, 'restTime' => 45],
                ['name' => 'Superman', 'sets' => 3, 'reps' => 10, 'restTime' => 45],
                
                // Extended mobility for recovery
                ['name' => 'Dynamic Pigeon', 'sets' => 3, 'duration' => 90, 'restTime' => 45],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 2, 'duration' => 90, 'restTime' => 0],
            ]
        ]);
    }

    private function createSpecialtyPlans(): void
    {
        echo "\n🎯 Creating Specialty Plans...\n";

        // Pure Mobility & Recovery
        $this->createWorkout([
            'name' => 'Mobilité & Récupération',
            'description' => 'Session complète de mobilité et d\'étirements',
            'category' => 'flexibility',
            'difficulty_level' => 'beginner',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 3, 'duration' => 60, 'restTime' => 30],
                ['name' => 'Dynamic Pigeon', 'sets' => 3, 'duration' => 90, 'restTime' => 45],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 3, 'duration' => 90, 'restTime' => 45],
                ['name' => 'Superman', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Glute Bridge', 'sets' => 2, 'reps' => 12, 'restTime' => 60],
                ['name' => 'Inchworm', 'sets' => 2, 'reps' => 5, 'restTime' => 60],
            ]
        ]);

        // Kettlebell Focus
        $this->createWorkout([
            'name' => 'Spécial Kettlebell',
            'description' => 'Entraînement centré sur les kettlebells',
            'category' => 'strength',
            'difficulty_level' => 'intermediate',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 2, 'duration' => 45, 'restTime' => 30],
                ['name' => 'Kettlebell Halo', 'sets' => 3, 'reps' => 10, 'restTime' => 75],
                ['name' => 'Double Kettlebell Front Squat', 'sets' => 4, 'reps' => 8, 'restTime' => 120],
                ['name' => 'Kettlebell Halo', 'sets' => 3, 'reps' => 8, 'restTime' => 75],
                ['name' => 'Squat', 'sets' => 3, 'reps' => 15, 'restTime' => 90],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);

        // Quick 15-Minute Express
        $this->createWorkout([
            'name' => 'Express 15 Minutes',
            'description' => 'Entraînement rapide et efficace pour les journées chargées',
            'category' => 'cardio',
            'difficulty_level' => 'intermediate',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 30, 'restTime' => 0],
                ['name' => 'Push-Up', 'sets' => 3, 'reps' => 8, 'restTime' => 30],
                ['name' => 'Squat', 'sets' => 3, 'reps' => 12, 'restTime' => 30],
                ['name' => 'Rotating Mountain Climber', 'sets' => 3, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Bicycle Crunch', 'sets' => 2, 'reps' => 15, 'restTime' => 30],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
            ]
        ]);

        // No Equipment Bodyweight Only
        $this->createWorkout([
            'name' => 'Sans Équipement',
            'description' => 'Entraînement complet au poids du corps sans matériel',
            'category' => 'strength',
            'difficulty_level' => 'intermediate',
            'exercises' => [
                ['name' => 'Hip Circles', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                ['name' => 'Push-Up Wide Grip', 'sets' => 3, 'reps' => 10, 'restTime' => 75],
                ['name' => 'Squat', 'sets' => 3, 'reps' => 15, 'restTime' => 75],
                ['name' => 'Superman', 'sets' => 3, 'reps' => 12, 'restTime' => 60],
                ['name' => 'Forward Lunge', 'sets' => 3, 'reps' => 12, 'restTime' => 75],
                ['name' => 'Crunch', 'sets' => 3, 'reps' => 15, 'restTime' => 60],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 2, 'duration' => 30, 'restTime' => 75],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 90, 'restTime' => 0],
            ]
        ]);
    }

    private function createWorkout(array $data): void
    {
        $exerciseData = $data['exercises'];
        unset($data['exercises']);

        $data['user_id'] = $this->user->id;
        $data['is_template'] = true; // These are predefined templates

        // Map weight_loss category to valid enum values
        if (($data['category'] ?? '') === 'weight_loss') {
            $data['category'] = 'hiit'; // Map weight loss to HIIT
        }

        // Process exercises and calculate estimates before creating the workout plan
        $processedData = $this->processExercises($exerciseData);
        
        $data['duration_minutes'] = $processedData['estimated_duration'];
        $data['calories_burned'] = $processedData['estimated_calories'];

        $workout = Workout::create($data);

        $pivotData = [];
        foreach ($processedData['exercises'] as $exerciseInfo) {
            $exerciseId = $exerciseInfo['exercise_id'];
            unset($exerciseInfo['exercise_id'], $exerciseInfo['exercise_name']);
            $pivotData[$exerciseId] = $exerciseInfo;
        }
        $workout->exercises()->attach($pivotData);
        
        echo "  ✅ {$workout->name} created (ID: {$workout->id}) for user {$this->user->name}\n";
    }

    private function processExercises(array $exerciseData): array
    {
        $exercises = [];
        $totalDuration = 0;
        $totalCalories = 0;
        
        foreach ($exerciseData as $index => $exerciseInfo) {
            $exercise = $this->exercises->get($exerciseInfo['name']);
            
            if ($exercise) {
                $exerciseEntry = [
                    'exercise_id' => $exercise->id,
                    'exercise_name' => $exercise->name,
                    'order_index' => $index,
                    'sets' => $exerciseInfo['sets'] ?? null,
                    'reps' => $exerciseInfo['reps'] ?? null,
                    'duration_seconds' => $exerciseInfo['duration'] ?? null,
                    'rest_time_seconds' => $exerciseInfo['restTime'] ?? 60,
                    'target_weight' => $exerciseInfo['weight'] ?? null,
                    'notes' => $exerciseInfo['notes'] ?? null,
                ];
                
                $exercises[] = $exerciseEntry;
                
                // Calculate estimated duration and calories
                $exerciseDuration = 0;
                if ($exerciseInfo['duration'] ?? null) {
                    $exerciseDuration = ($exerciseInfo['duration'] * ($exerciseInfo['sets'] ?? 1)) / 60;
                } else {
                    $exerciseDuration = (($exerciseInfo['reps'] ?? 10) * 3 * ($exerciseInfo['sets'] ?? 1)) / 60; // Estimate 3 seconds per rep
                }
                $restDuration = (($exerciseInfo['restTime'] ?? 60) * (($exerciseInfo['sets'] ?? 1) - 1)) / 60;
                
                $totalDuration += $exerciseDuration + $restDuration;
                $totalCalories += $exerciseDuration * ($exercise->estimated_calories_per_minute ?? 5);
            } else {
                echo "  ⚠️  Exercise '{$exerciseInfo['name']}' not found\n";
            }
        }
        
        return [
            'exercises' => empty($exercises) ? [] : $exercises,
            'estimated_duration' => (int) ceil(max(1, $totalDuration)), // Minimum 1 minute
            'estimated_calories' => (int) round(max(10, $totalCalories)), // Minimum 10 calories
        ];
    }

    private function displayStatistics(): void
    {
        $workouts = Workout::where('is_template', true)->where('user_id', $this->user->id)->get();
        
        $stats = [
            'Total' => $workouts->count(),
            'Beginner' => $workouts->where('difficulty_level', 'beginner')->count(),
            'Intermediate' => $workouts->where('difficulty_level', 'intermediate')->count(),
            'Advanced' => $workouts->where('difficulty_level', 'advanced')->count(),
            'Strength' => $workouts->where('category', 'strength')->count(),
            'Cardio' => $workouts->where('category', 'cardio')->count(),
            'HIIT' => $workouts->where('category', 'hiit')->count(),
            'Flexibility' => $workouts->where('category', 'flexibility')->count(),
        ];
        
        echo "\n📊 Workout Plan Statistics for {$this->user->name}:\n";
        foreach ($stats as $key => $count) {
            echo "   - {$key}: {$count}\n";
        }
        
        $totalDuration = $workouts->sum('duration_minutes');
        $totalCalories = $workouts->sum('calories_burned');
        
        echo "\n📈 Aggregated Data:\n";
        echo "   - Total Duration: {$totalDuration} minutes\n";
        echo "   - Total Calories: {$totalCalories} calories\n";
        
        $hiitWorkouts = $workouts->where('category', 'hiit');
        echo "\n🔥 HIIT/Weight Loss Workouts Created:\n";
        foreach ($hiitWorkouts as $workout) {
            echo "   - {$workout->name} ({$workout->difficulty_level}) - {$workout->duration_minutes}min\n";
        }
        
        echo "\n🎯 Features implemented:\n";
        echo "   - ✅ Templates assigned to: {$this->user->name} (ID: {$this->user->id})\n";
        echo "   - ✅ Progressive difficulty scaling\n";
        echo "   - ✅ Balanced muscle group targeting\n";
        echo "   - ✅ Warm-up and cool-down inclusion\n";
        echo "   - ✅ Varied workout types and focuses\n";
        echo "   - ✅ Smart exercise selection\n";
        echo "   - ✅ Appropriate rest times per level\n";
        echo "   - ✅ Equipment-based categorization\n";
        echo "   - ✅ Weight loss specific programs (mapped to HIIT)\n";
        echo "   - ✅ HIIT protocols for fat burning\n";
        echo "   - ✅ Circuit training methodologies\n";
        echo "   - ✅ Intensity progression (Light → Regular → Intensive)\n";
        echo "   - ✅ Optimal calorie burning exercise selection\n";
        echo "   - ✅ JSON-based exercise storage with duration/calorie estimation\n";
    }
}