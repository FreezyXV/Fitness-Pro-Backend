<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workout;
use App\Models\User;
use App\Models\Exercise;
use Illuminate\Support\Collection;
use App\Support\SystemUserResolver;

class WorkoutPlansSeeder extends Seeder
{
    private Collection $exercises;
    private User $user;

    public function run(): void
    {
        echo "🏃‍♂️ Creating intelligent workout templates...\n";

        // Get or create a user for templates
        $this->user = $this->getOrCreateUser();
        echo "✅ Using user: {$this->user->email} (ID: {$this->user->id})\n";

        // Clean existing templates for this user
        $this->cleanExistingTemplates();

        // Load all exercises and verify they exist
        $this->loadExercises();
        
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

    private function getOrCreateUser(): User
    {
        $user = SystemUserResolver::resolve();

        if ($user->wasRecentlyCreated) {
            echo "✅ Created system template user: {$user->email} (ID: {$user->id})\n";
        }

        return $user;
    }

    private function cleanExistingTemplates(): void
    {
        $existingCount = Workout::where('user_id', $this->user->id)
                               ->where('is_template', true)
                               ->count();
        
        if ($existingCount > 0) {
            echo "🗑️ Removing {$existingCount} existing templates...\n";
            Workout::where('user_id', $this->user->id)
                   ->where('is_template', true)
                   ->delete();
        }
    }

    private function loadExercises(): void
    {
        $this->exercises = Exercise::all()->keyBy('name');
        
        // Log available exercises for debugging
        echo "📋 Available exercises:\n";
        foreach ($this->exercises->keys()->take(10) as $name) {
            echo "   - {$name}\n";
        }
        if ($this->exercises->count() > 10) {
            echo "   ... and " . ($this->exercises->count() - 10) . " more\n";
        }
    }

    private function createBeginnerPlans(): void
    {
        echo "\n📚 Creating Beginner Plans...\n";

        // Beginner Full Body Starter
        $this->createWorkout([
            'name' => 'Programme Débutant - Corps Entier',
            'description' => 'Introduction parfaite au fitness avec des mouvements de base pour tout le corps',
            'type' => 'strength',
            'category' => 'strength',
            'difficulty' => 'beginner',
            'difficulty_level' => 'beginner',
            'exercises' => [
                // Warm-up
                ['name' => '90:90 Hip Crossover', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                
                // Main workout
                ['name' => 'Push-Up', 'sets' => 2, 'reps' => 5, 'restTime' => 60],
                ['name' => 'Prisoner Squat', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Superman', 'sets' => 2, 'reps' => 6, 'restTime' => 60],
                ['name' => 'Bodyweight Glute Bridge', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Crunch', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                
                // Cool-down
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 90, 'restTime' => 0],
            ]
        ]);

        // Beginner Upper Body Focus
        $this->createWorkout([
            'name' => 'Débutant - Haut du Corps',
            'description' => 'Renforcement progressif des bras, épaules et poitrine',
            'type' => 'strength',
            'category' => 'strength',
            'difficulty' => 'beginner',
            'difficulty_level' => 'beginner',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 1, 'duration' => 45, 'restTime' => 0],
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
            'type' => 'strength',
            'category' => 'strength',
            'difficulty' => 'beginner',
            'difficulty_level' => 'beginner',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                ['name' => 'Prisoner Squat', 'sets' => 3, 'reps' => 10, 'restTime' => 90],
                ['name' => 'Bodyweight Glute Bridge', 'sets' => 3, 'reps' => 12, 'restTime' => 60],
                ['name' => 'Donkey Kicks', 'sets' => 2, 'reps' => 8, 'restTime' => 45],
                ['name' => 'Forward Lunge', 'sets' => 2, 'reps' => 6, 'restTime' => 75],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 90, 'restTime' => 0],
            ]
        ]);

        // Beginner Cardio & Mobility
        $this->createWorkout([
            'name' => 'Débutant - Cardio & Mobilité',
            'description' => 'Amélioration de l\'endurance et de la flexibilité',
            'type' => 'cardio',
            'category' => 'cardio',
            'difficulty' => 'beginner',
            'difficulty_level' => 'beginner',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 2, 'duration' => 45, 'restTime' => 30],
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
            'type' => 'strength',
            'difficulty' => 'intermediate',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
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
            'type' => 'strength',
            'difficulty' => 'intermediate',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 2, 'duration' => 45, 'restTime' => 30],
                ['name' => 'Prisoner Squat', 'sets' => 4, 'reps' => 12, 'restTime' => 90],
                ['name' => 'Forward Lunge', 'sets' => 3, 'reps' => 10, 'restTime' => 90],
                ['name' => 'Alternating Lateral Lunge', 'sets' => 3, 'reps' => 12, 'restTime' => 90],
                ['name' => 'Fire Hydrant Circles', 'sets' => 3, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Bodyweight Glute Bridge', 'sets' => 3, 'reps' => 15, 'restTime' => 60],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);

        // Intermediate Full Body
        $this->createWorkout([
            'name' => 'Intermédiaire - Circuit Corps Entier',
            'description' => 'Entraînement en circuit pour force et endurance',
            'type' => 'strength',
            'difficulty' => 'intermediate',
            'exercises' => [
                ['name' => 'Inchworm', 'sets' => 2, 'reps' => 6, 'restTime' => 45],
                ['name' => 'Push-Up Wide Grip', 'sets' => 3, 'reps' => 10, 'restTime' => 60],
                ['name' => 'Prisoner Squat', 'sets' => 3, 'reps' => 15, 'restTime' => 60],
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
            'type' => 'hiit',
            'difficulty' => 'intermediate',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                ['name' => 'Inchworm', 'sets' => 3, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Rotating Mountain Climber', 'sets' => 4, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Push-Up', 'sets' => 4, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Prisoner Squat', 'sets' => 4, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Bicycle Crunch', 'sets' => 3, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);

        // Core Focus
        $this->createWorkout([
            'name' => 'Intermédiaire - Spécial Abdos',
            'description' => 'Renforcement complet du tronc et des abdominaux',
            'type' => 'strength',
            'difficulty' => 'intermediate',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 1, 'duration' => 45, 'restTime' => 0],
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
            'type' => 'strength',
            'difficulty' => 'advanced',
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
            'type' => 'strength',
            'difficulty' => 'advanced',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 2, 'duration' => 45, 'restTime' => 30],
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
            'type' => 'strength',
            'difficulty' => 'advanced',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 2, 'duration' => 60, 'restTime' => 30],
                ['name' => 'Double Kettlebell Front Squat', 'sets' => 4, 'reps' => 10, 'restTime' => 135],
                ['name' => 'Prisoner Squat', 'sets' => 4, 'reps' => 20, 'restTime' => 120],
                ['name' => 'Forward Lunge', 'sets' => 4, 'reps' => 15, 'restTime' => 105],
                ['name' => 'Alternating Lateral Lunge', 'sets' => 3, 'reps' => 16, 'restTime' => 90],
                ['name' => 'Fire Hydrant Circles', 'sets' => 3, 'reps' => 12, 'restTime' => 75],
                ['name' => 'Bodyweight Glute Bridge', 'sets' => 4, 'reps' => 20, 'restTime' => 75],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 150, 'restTime' => 0],
            ]
        ]);

        // Advanced Full Body Beast Mode
        $this->createWorkout([
            'name' => 'Avancé - Beast Mode',
            'description' => 'Entraînement ultime pour athlètes confirmés',
            'type' => 'strength',
            'difficulty' => 'advanced',
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
            'type' => 'hiit',
            'difficulty' => 'advanced',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                ['name' => 'Inchworm', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Spider-Man Push-Up', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Rotating Mountain Climber', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Prisoner Squat', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Bicycle Crunch', 'sets' => 4, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 180, 'restTime' => 0],
            ]
        ]);
    }

    private function createWeightLossPlans(): void
    {
        echo "\n🔥 Creating Weight Loss Plans...\n";

        // Weight Loss - Light Version
        $this->createWorkout([
            'name' => 'Perte de Poids - Version Légère',
            'description' => 'Programme doux pour débuter la perte de poids',
            'type' => 'hiit',
            'difficulty' => 'beginner',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 2, 'duration' => 60, 'restTime' => 20],
                ['name' => 'Inchworm', 'sets' => 2, 'reps' => 4, 'restTime' => 30],
                ['name' => 'Prisoner Squat', 'sets' => 3, 'reps' => 15, 'restTime' => 45],
                ['name' => 'Push-Up', 'sets' => 3, 'reps' => 6, 'restTime' => 45],
                ['name' => 'Rotating Mountain Climber', 'sets' => 3, 'duration' => 20, 'restTime' => 45],
                ['name' => 'Bodyweight Glute Bridge', 'sets' => 3, 'reps' => 15, 'restTime' => 45],
                ['name' => 'Crunch', 'sets' => 3, 'reps' => 12, 'restTime' => 45],
                ['name' => 'Alternate Heel Touches', 'sets' => 2, 'reps' => 16, 'restTime' => 30],
                ['name' => 'Dynamic Pigeon', 'sets' => 2, 'duration' => 60, 'restTime' => 30],
            ]
        ]);

        // Weight Loss - Regular Mode
        $this->createWorkout([
            'name' => 'Perte de Poids - Mode Régulier',
            'description' => 'Entraînement équilibré pour une perte de poids progressive',
            'type' => 'hiit',
            'difficulty' => 'intermediate',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 2, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Inchworm', 'sets' => 3, 'reps' => 6, 'restTime' => 30],
                ['name' => 'Prisoner Squat', 'sets' => 4, 'reps' => 18, 'restTime' => 30],
                ['name' => 'Forward Lunge', 'sets' => 3, 'reps' => 14, 'restTime' => 30],
                ['name' => 'Alternating Lateral Lunge', 'sets' => 3, 'reps' => 16, 'restTime' => 30],
                ['name' => 'Push-Up Wide Grip', 'sets' => 4, 'reps' => 10, 'restTime' => 30],
                ['name' => 'Rotating Mountain Climber', 'sets' => 4, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Bicycle Crunch', 'sets' => 4, 'reps' => 24, 'restTime' => 30],
                ['name' => 'Bodyweight Glute Bridge', 'sets' => 3, 'reps' => 20, 'restTime' => 30],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 3, 'duration' => 25, 'restTime' => 45],
                ['name' => 'Straight Leg Toe Touch', 'sets' => 3, 'reps' => 16, 'restTime' => 30],
                ['name' => 'Dynamic Pigeon', 'sets' => 2, 'duration' => 90, 'restTime' => 30],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 90, 'restTime' => 0],
            ]
        ]);

        // Weight Loss - Intensive Mode
        $this->createWorkout([
            'name' => 'Perte de Poids - Mode Intensif',
            'description' => 'Programme haute intensité pour maximiser la combustion',
            'type' => 'hiit',
            'difficulty' => 'advanced',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 2, 'duration' => 45, 'restTime' => 10],
                ['name' => 'Inchworm', 'sets' => 3, 'reps' => 8, 'restTime' => 20],
                ['name' => 'Prisoner Squat', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Spider-Man Push-Up', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Rotating Mountain Climber', 'sets' => 5, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Forward Lunge', 'sets' => 4, 'duration' => 45, 'restTime' => 15],
                ['name' => 'Push-Up Wide Grip', 'sets' => 4, 'duration' => 40, 'restTime' => 20],
                ['name' => 'Alternating Lateral Lunge', 'sets' => 4, 'duration' => 40, 'restTime' => 20],
                ['name' => 'Bicycle Crunch', 'sets' => 4, 'duration' => 40, 'restTime' => 20],
                ['name' => 'Fire Hydrant Circles', 'sets' => 3, 'duration' => 40, 'restTime' => 20],
                ['name' => 'Bodyweight Glute Bridge', 'sets' => 4, 'duration' => 30, 'restTime' => 10],
                ['name' => 'Side Plank with Hip Dip', 'sets' => 4, 'duration' => 30, 'restTime' => 10],
                ['name' => 'Sit-Up', 'sets' => 3, 'duration' => 30, 'restTime' => 10],
                ['name' => 'Dynamic Pigeon', 'sets' => 2, 'duration' => 120, 'restTime' => 45],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
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
            'type' => 'flexibility',
            'difficulty' => 'beginner',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 3, 'duration' => 60, 'restTime' => 30],
                ['name' => 'Dynamic Pigeon', 'sets' => 3, 'duration' => 90, 'restTime' => 45],
                ['name' => 'Lateral Kneeling Adductor Mobilization', 'sets' => 3, 'duration' => 90, 'restTime' => 45],
                ['name' => 'Superman', 'sets' => 2, 'reps' => 8, 'restTime' => 60],
                ['name' => 'Bodyweight Glute Bridge', 'sets' => 2, 'reps' => 12, 'restTime' => 60],
                ['name' => 'Inchworm', 'sets' => 2, 'reps' => 5, 'restTime' => 60],
            ]
        ]);

        // Kettlebell Focus
        $this->createWorkout([
            'name' => 'Spécial Kettlebell',
            'description' => 'Entraînement centré sur les kettlebells',
            'type' => 'strength',
            'difficulty' => 'intermediate',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 2, 'duration' => 45, 'restTime' => 30],
                ['name' => 'Kettlebell Halo', 'sets' => 3, 'reps' => 10, 'restTime' => 75],
                ['name' => 'Double Kettlebell Front Squat', 'sets' => 4, 'reps' => 8, 'restTime' => 120],
                ['name' => 'Kettlebell Halo', 'sets' => 3, 'reps' => 8, 'restTime' => 75],
                ['name' => 'Prisoner Squat', 'sets' => 3, 'reps' => 15, 'restTime' => 90],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 120, 'restTime' => 0],
            ]
        ]);

        // Quick 15-Minute Express
        $this->createWorkout([
            'name' => 'Express 15 Minutes',
            'description' => 'Entraînement rapide et efficace pour les journées chargées',
            'type' => 'cardio',
            'difficulty' => 'intermediate',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 1, 'duration' => 30, 'restTime' => 0],
                ['name' => 'Push-Up', 'sets' => 3, 'reps' => 8, 'restTime' => 30],
                ['name' => 'Prisoner Squat', 'sets' => 3, 'reps' => 12, 'restTime' => 30],
                ['name' => 'Rotating Mountain Climber', 'sets' => 3, 'duration' => 30, 'restTime' => 30],
                ['name' => 'Bicycle Crunch', 'sets' => 2, 'reps' => 15, 'restTime' => 30],
                ['name' => 'Dynamic Pigeon', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
            ]
        ]);

        // No Equipment Bodyweight Only
        $this->createWorkout([
            'name' => 'Sans Équipement',
            'description' => 'Entraînement complet au poids du corps sans matériel',
            'type' => 'strength',
            'difficulty' => 'intermediate',
            'exercises' => [
                ['name' => '90:90 Hip Crossover', 'sets' => 1, 'duration' => 60, 'restTime' => 0],
                ['name' => 'Push-Up Wide Grip', 'sets' => 3, 'reps' => 10, 'restTime' => 75],
                ['name' => 'Prisoner Squat', 'sets' => 3, 'reps' => 15, 'restTime' => 75],
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
        $data['is_template'] = true;
        $data['status'] = 'planned';

        // Calculate estimates
        $processedData = $this->processExercises($exerciseData);
        
        $data['estimated_duration'] = $processedData['estimated_duration'];
        $data['estimated_calories'] = $processedData['estimated_calories'];

        $workout = Workout::create($data);

        // Attach exercises with pivot data
        $pivotData = [];
        foreach ($processedData['exercises'] as $exerciseInfo) {
            $exerciseId = $exerciseInfo['exercise_id'];
            unset($exerciseInfo['exercise_id'], $exerciseInfo['exercise_name']);
            $pivotData[$exerciseId] = $exerciseInfo;
        }
        
        if (!empty($pivotData)) {
            $workout->exercises()->attach($pivotData);
        }
        
        echo "  ✅ {$workout->name} created (ID: {$workout->id})\n";
    }

    private function processExercises(array $exerciseData): array
    {
        $exercises = [];
        $totalDuration = 0;
        $totalCalories = 0;
        $notFoundExercises = [];
        
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
                    $exerciseDuration = (($exerciseInfo['reps'] ?? 10) * 3 * ($exerciseInfo['sets'] ?? 1)) / 60;
                }
                $restDuration = (($exerciseInfo['restTime'] ?? 60) * (($exerciseInfo['sets'] ?? 1) - 1)) / 60;
                
                $totalDuration += $exerciseDuration + $restDuration;
                $totalCalories += $exerciseDuration * ($exercise->estimated_calories ?? 5);
            } else {
                $notFoundExercises[] = $exerciseInfo['name'];
            }
        }
        
        if (!empty($notFoundExercises)) {
            echo "  ⚠️  Exercises not found: " . implode(', ', $notFoundExercises) . "\n";
        }
        
        return [
            'exercises' => $exercises,
            'estimated_duration' => (int) ceil(max(1, $totalDuration)),
            'estimated_calories' => (int) round(max(10, $totalCalories)),
        ];
    }

    private function displayStatistics(): void
    {
        $workouts = Workout::where('is_template', true)
                          ->where('user_id', $this->user->id)
                          ->get();
        
        $stats = [
            'Total' => $workouts->count(),
            'Beginner' => $workouts->where('difficulty', 'beginner')->count(),
            'Intermediate' => $workouts->where('difficulty', 'intermediate')->count(),
            'Advanced' => $workouts->where('difficulty', 'advanced')->count(),
            'Strength' => $workouts->where('type', 'strength')->count(),
            'Cardio' => $workouts->where('type', 'cardio')->count(),
            'HIIT' => $workouts->where('type', 'hiit')->count(),
            'Flexibility' => $workouts->where('type', 'flexibility')->count(),
        ];
        
        echo "\n📊 Workout Plan Statistics for {$this->user->name}:\n";
        foreach ($stats as $key => $count) {
            echo "   - {$key}: {$count}\n";
        }
        
        $totalDuration = $workouts->sum('estimated_duration');
        $totalCalories = $workouts->sum('estimated_calories');
        
        echo "\n📈 Aggregated Data:\n";
        echo "   - Total Duration: {$totalDuration} minutes\n";
        echo "   - Total Calories: {$totalCalories} calories\n";
        
        echo "\n✅ All exercise names matched successfully!\n";
    }
}
