<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercise;
use Illuminate\Support\Facades\DB;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing exercises (PostgreSQL compatible)
        try {
            // For PostgreSQL, we can use TRUNCATE with CASCADE
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('TRUNCATE TABLE exercises RESTART IDENTITY CASCADE;');
            } else {
                // For MySQL
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                Exercise::truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        } catch (\Exception $e) {
            // Fallback: simple delete
            Exercise::query()->delete();
        }

        $exercises = [
            // CHEST EXERCISES
            [
                'name' => 'Push-Up',
                'body_part' => 'chest',
                'description' => 'Exercice de pompes classique pour développer la force du haut du corps.',
                'video_url' => 'https://i.imgur.com/cuvjCQo.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['pectoraux', 'triceps', 'deltoïdes'],
                'equipment' => 'aucun',
                'category' => 'strength',
                'estimated_calories' =>8,
                'instructions' => [
                    'Placez-vous en position de planche',
                    'Descendez en fléchissant les bras',
                    'Poussez pour revenir à la position de départ'
                ],
                'tips' => [
                    'Gardez le corps aligné',
                    'Contrôlez la descente'
                ]
            ],
            [
                'name' => 'Push-Up Wide Grip',
                'body_part' => 'chest',
                'description' => 'Variante des pompes avec mains écartées pour cibler la poitrine.',
                'video_url' => 'https://i.imgur.com/VBg0a0L.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['pectoraux', 'triceps'],
                'equipment' => 'aucun',
                'category' => 'strength',
                'estimated_calories' =>9,
                'instructions' => [
                    'Placez vos mains plus écartées',
                    'Descendez lentement',
                    'Contractez les pectoraux'
                ],
                'tips' => [
                    'Évitez de descendre trop bas'
                ]
            ],
            [
                'name' => 'Spider-Man Push-Up',
                'body_part' => 'chest',
                'description' => 'Pompes avec genou vers le coude pour engager le tronc.',
                'video_url' => 'https://i.imgur.com/R2OuzXL.mp4',
                'duration' => 1,
                'difficulty' => 'advanced',
                'muscle_groups' => ['pectoraux', 'triceps', 'obliques'],
                'equipment' => 'aucun',
                'category' => 'strength',
                'estimated_calories' =>10,
                'instructions' => [
                    'Position de pompe',
                    'Descendez en amenant le genou vers le coude',
                    'Alternez les côtés'
                ],
                'tips' => [
                    'Gardez les hanches stables'
                ]
            ],

            // BACK EXERCISES
            [
                'name' => 'Pull-Up',
                'body_part' => 'back',
                'description' => 'Tractions paumes vers avant pour développer le dos et les bras.',
                'video_url' => 'https://i.imgur.com/7hSgn9j.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['grand dorsal', 'biceps', 'trapèzes'],
                'equipment' => 'barre de traction',
                'category' => 'strength',
                'estimated_calories' =>10,
                'instructions' => [
                    'Suspendez-vous à la barre',
                    'Tirez votre corps vers le haut',
                    'Descendez lentement'
                ],
                'tips' => [
                    'Engagez les muscles du dos',
                    'Ne vous balancez pas'
                ]
            ],
            [
                'name' => 'Pull-Up Wide Grip',
                'body_part' => 'back',
                'description' => 'Tractions prise large pour cibler le grand dorsal.',
                'video_url' => 'https://i.imgur.com/iqWHOVf.mp4',
                'duration' => 1,
                'difficulty' => 'advanced',
                'muscle_groups' => ['grand dorsal', 'trapèzes', 'rhomboïdes'],
                'equipment' => 'barre de traction',
                'category' => 'strength',
                'estimated_calories' =>11,
                'instructions' => [
                    'Prise plus large que les épaules',
                    'Tirez en écartant les coudes',
                    'Contractez les omoplates'
                ],
                'tips' => [
                    'Plus difficile que les tractions classiques'
                ]
            ],
            [
                'name' => 'Chin-Up',
                'body_part' => 'back',
                'description' => 'Tractions paumes vers soi pour biceps et dos.',
                'video_url' => 'https://i.imgur.com/fVhJ8Xr.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['biceps', 'grand dorsal', 'rhomboïdes'],
                'equipment' => 'barre de traction',
                'category' => 'strength',
                'estimated_calories' =>10,
                'instructions' => [
                    'Prise en supination',
                    'Tirez le menton au-dessus de la barre',
                    'Descendez contrôlé'
                ],
                'tips' => [
                    'Plus facile que les pull-ups'
                ]
            ],
            [
                'name' => 'Superman',
                'body_part' => 'back',
                'description' => 'Exercice au sol pour renforcer les lombaires.',
                'video_url' => 'https://i.imgur.com/xfISRpJ.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['lombaires', 'fessiers'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>5,
                'instructions' => [
                    'Allongez-vous sur le ventre',
                    'Levez buste et jambes',
                    'Maintenez la position'
                ],
                'tips' => [
                    'Ne forcez pas sur le cou'
                ]
            ],

            // LEGS EXERCISES
            [
                'name' => 'Prisoner Squat',
                'body_part' => 'legs',
                'description' => 'Exercice fondamental pour les jambes et les fessiers avec mains derrière la tête.',
                'video_url' => 'https://i.imgur.com/C84xqhn.mp4',
                'duration' => 2,
                'difficulty' => 'beginner',
                'muscle_groups' => ['quadriceps', 'fessiers', 'mollets'],
                'equipment' => 'aucun',
                'category' => 'strength',
                'estimated_calories' =>8,
                'instructions' => [
                    'Pieds écartés largeur épaules',
                    'Mains derrière la tête',
                    'Descendez en fléchissant les genoux',
                    'Remontez en poussant sur les talons'
                ],
                'tips' => [
                    'Gardez le dos droit',
                    'Genoux alignés avec les orteils'
                ]
            ],
            [
                'name' => 'Forward Lunge',
                'body_part' => 'legs',
                'description' => 'Fentes avant pour travailler les jambes de manière unilatérale.',
                'video_url' => 'https://i.imgur.com/fVvjWEM.mp4',
                'duration' => 2,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['quadriceps', 'fessiers', 'ischio-jambiers'],
                'equipment' => 'aucun',
                'category' => 'strength',
                'estimated_calories' =>8,
                'instructions' => [
                    'Faites un grand pas vers l\'avant',
                    'Descendez jusqu\'à 90°',
                    'Revenez à la position de départ'
                ],
                'tips' => [
                    'Le genou ne doit pas dépasser l\'orteil'
                ]
            ],
            [
                'name' => 'Alternating Lateral Lunge',
                'body_part' => 'legs',
                'description' => 'Fentes latérales alternées pour travailler les adducteurs.',
                'video_url' => 'https://i.imgur.com/EdGmxKm.mp4',
                'duration' => 2,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['quadriceps', 'fessiers', 'adducteurs'],
                'equipment' => 'aucun',
                'category' => 'strength',
                'estimated_calories' =>9,
                'instructions' => [
                    'Faites un pas sur le côté',
                    'Fléchissez la jambe d\'appui',
                    'Alternez droite et gauche'
                ],
                'tips' => [
                    'Gardez le torse droit'
                ]
            ],
            [
                'name' => 'Bodyweight Glute Bridge',
                'body_part' => 'legs',
                'description' => 'Pont de fessiers pour renforcer la chaîne postérieure.',
                'video_url' => 'https://i.imgur.com/ZnSPjsz.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['fessiers', 'ischio-jambiers'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>6,
                'instructions' => [
                    'Allongé sur le dos, genoux fléchis',
                    'Soulevez les hanches',
                    'Contractez les fessiers'
                ],
                'tips' => [
                    'Ne creusez pas le dos'
                ]
            ],
            [
                'name' => 'Donkey Kicks',
                'body_part' => 'legs',
                'description' => 'Extensions de hanche à quatre pattes pour les fessiers.',
                'video_url' => 'https://i.imgur.com/vx0ypiy.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['fessiers', 'ischio-jambiers'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>6,
                'instructions' => [
                    'À quatre pattes',
                    'Levez une jambe vers l\'arrière',
                    'Contractez les fessiers'
                ],
                'tips' => [
                    'Gardez le dos plat'
                ]
            ],
            [
                'name' => 'Fire Hydrant Circles',
                'body_part' => 'legs',
                'description' => 'Cercles avec la jambe pour les fessiers et stabilisateurs.',
                'video_url' => 'https://i.imgur.com/KmcebcY.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['fessiers', 'abducteurs'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>7,
                'instructions' => [
                    'À quatre pattes',
                    'Levez la jambe sur le côté',
                    'Effectuez des cercles'
                ],
                'tips' => [
                    'Mouvements contrôlés'
                ]
            ],

            // SHOULDERS EXERCISES
            [
                'name' => 'Standing Dumbbell Shrug',
                'body_part' => 'shoulders',
                'description' => 'Haussements d\'épaules avec haltères pour les trapèzes.',
                'video_url' => 'https://i.imgur.com/5VHJDAo.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['trapèzes'],
                'equipment' => 'haltères',
                'category' => 'strength',
                'estimated_calories' =>5,
                'instructions' => [
                    'Debout, haltères dans les mains',
                    'Haussez les épaules vers les oreilles',
                    'Redescendez lentement'
                ],
                'tips' => [
                    'Ne roulez pas les épaules'
                ]
            ],
            [
                'name' => 'Kettlebell Halo',
                'body_part' => 'shoulders',
                'description' => 'Rotation de kettlebell autour de la tête pour les épaules.',
                'video_url' => 'https://i.imgur.com/qnpAlNR.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['deltoïdes', 'rotateurs', 'tronc'],
                'equipment' => 'kettlebell',
                'category' => 'strength',
                'estimated_calories' =>7,
                'instructions' => [
                    'Tenez la kettlebell par les cornes',
                    'Faites des cercles autour de la tête',
                    'Changez de direction'
                ],
                'tips' => [
                    'Gardez le tronc engagé'
                ]
            ],

            // ARMS EXERCISES
            [
                'name' => 'Seated Concentration Curl',
                'body_part' => 'arms',
                'description' => 'Curl biceps assis avec concentration.',
                'video_url' => 'https://i.imgur.com/9w47QJA.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['biceps'],
                'equipment' => 'haltères',
                'category' => 'strength',
                'estimated_calories' =>5,
                'instructions' => [
                    'Assis, coude contre la cuisse',
                    'Fléchissez l\'avant-bras',
                    'Contractez le biceps'
                ],
                'tips' => [
                    'Gardez le coude fixe'
                ]
            ],
            [
                'name' => 'Seated Overhead Dumbbell Tricep Extension',
                'body_part' => 'arms',
                'description' => 'Extension triceps assis avec haltère.',
                'video_url' => 'https://i.imgur.com/rBJLyMy.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['triceps'],
                'equipment' => 'haltères',
                'category' => 'strength',
                'estimated_calories' =>6,
                'instructions' => [
                    'Assis, haltère au-dessus de la tête',
                    'Descendez l\'haltère derrière la tête',
                    'Remontez en contractant les triceps'
                ],
                'tips' => [
                    'Gardez les coudes près de la tête'
                ]
            ],

            // ABS EXERCISES
            [
                'name' => 'Side Plank with Hip Dip',
                'body_part' => 'abs',
                'description' => 'Gainage latéral avec mouvement de hanches.',
                'video_url' => 'https://i.imgur.com/v48q65S.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['obliques', 'transverse'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>7,
                'instructions' => [
                    'Position de planche latérale',
                    'Descendez et remontez la hanche',
                    'Gardez l\'alignement'
                ],
                'tips' => [
                    'Contrôlez le mouvement'
                ]
            ],
            [
                'name' => 'Crunch',
                'body_part' => 'abs',
                'description' => 'Flexion de buste pour les abdominaux.',
                'video_url' => 'https://i.imgur.com/NufQaAA.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['grand droit'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>6,
                'instructions' => [
                    'Allongé sur le dos',
                    'Soulevez les épaules du sol',
                    'Contractez les abdominaux'
                ],
                'tips' => [
                    'Ne tirez pas sur le cou'
                ]
            ],
            [
                'name' => 'Oblique Crunch',
                'body_part' => 'abs',
                'description' => 'Crunch latéral pour les obliques.',
                'video_url' => 'https://i.imgur.com/K1xe6Rs.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['obliques'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>6,
                'instructions' => [
                    'Allongé sur le côté',
                    'Contractez les obliques',
                    'Rapprochez coude et genou'
                ],
                'tips' => [
                    'Mouvement contrôlé'
                ]
            ],
            [
                'name' => 'Sit-Up',
                'body_part' => 'abs',
                'description' => 'Redressement assis complet pour les abdominaux.',
                'video_url' => 'https://i.imgur.com/DOTR5Xp.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['grand droit', 'fléchisseurs de hanche'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>7,
                'instructions' => [
                    'Allongé sur le dos, genoux fléchis',
                    'Montez jusqu\'à la position assise',
                    'Redescendez contrôlé'
                ],
                'tips' => [
                    'Engagez les abdominaux'
                ]
            ],
            [
                'name' => 'Bicycle Crunch',
                'body_part' => 'abs',
                'description' => 'Pédalage pour travailler obliques et grand droit.',
                'video_url' => 'https://i.imgur.com/lxEWj3F.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['obliques', 'grand droit'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>8,
                'instructions' => [
                    'Allongé sur le dos',
                    'Alternez coude vers genou opposé',
                    'Mouvement de pédalage'
                ],
                'tips' => [
                    'Ne tirez pas sur le cou'
                ]
            ],
            [
                'name' => 'Rotating Mountain Climber',
                'body_part' => 'abs',
                'description' => 'Mountain climber avec rotation pour les obliques.',
                'video_url' => 'https://i.imgur.com/D4W7JaW.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['transverse', 'obliques'],
                'equipment' => 'aucun',
                'category' => 'cardio',
                'estimated_calories' =>12,
                'instructions' => [
                    'Position de planche',
                    'Amenez le genou vers le coude opposé',
                    'Alternez rapidement'
                ],
                'tips' => [
                    'Gardez les hanches stables'
                ]
            ],
            [
                'name' => 'Straight Leg Toe Touch',
                'body_part' => 'abs',
                'description' => 'Toucher d\'orteils jambes tendues pour les abdominaux.',
                'video_url' => 'https://i.imgur.com/Lnnuzpn.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['grand droit', 'obliques'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>6,
                'instructions' => [
                    'Allongé sur le dos, jambes tendues',
                    'Montez les mains vers les orteils',
                    'Contractez les abdominaux'
                ],
                'tips' => [
                    'Gardez les jambes droites'
                ]
            ],
            [
                'name' => 'Alternate Heel Touches',
                'body_part' => 'abs',
                'description' => 'Touches de talons alternées pour les obliques.',
                'video_url' => 'https://i.imgur.com/86zmQ0q.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['obliques'],
                'equipment' => 'tapis',
                'category' => 'strength',
                'estimated_calories' =>5,
                'instructions' => [
                    'Allongé sur le dos, genoux fléchis',
                    'Touchez alternativement les talons',
                    'Contractez les obliques'
                ],
                'tips' => [
                    'Mouvement latéral contrôlé'
                ]
            ],

            // CARDIO EXERCISES
            [
                'name' => 'Inchworm',
                'body_part' => 'cardio',
                'description' => 'Mouvement du ver pour échauffement et cardio.',
                'video_url' => 'https://i.imgur.com/vjDWvQ0.mp4',
                'duration' => 1,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['corps entier'],
                'equipment' => 'aucun',
                'category' => 'cardio',
                'estimated_calories' =>8,
                'instructions' => [
                    'Debout, penchez-vous vers l\'avant',
                    'Marchez avec les mains vers la planche',
                    'Revenez en marchant vers les pieds'
                ],
                'tips' => [
                    'Gardez les jambes droites'
                ]
            ],

            // MOBILITY EXERCISES
            [
                'name' => '90:90 Hip Crossover',
                'body_part' => 'mobility',
                'description' => 'Mobilisation des hanches en position 90:90.',
                'video_url' => 'https://imgur.com/SwccHLF.mp4',
                'duration' => 1,
                'difficulty' => 'beginner',
                'muscle_groups' => ['fléchisseurs de hanche', 'rotateurs de hanche'],
                'equipment' => 'aucun',
                'category' => 'mobility',
                'estimated_calories' =>4,
                'instructions' => [
                    'Assis, jambes en position 90:90',
                    'Basculez d\'un côté à l\'autre',
                    'Gardez le buste droit'
                ],
                'tips' => [
                    'Mouvements lents et contrôlés'
                ]
            ],
            [
                'name' => 'Lateral Kneeling Adductor Mobilization',
                'body_part' => 'mobility',
                'description' => 'Mobilisation des adducteurs en position agenouillée.',
                'video_url' => 'https://i.imgur.com/eP49InA.mp4',
                'duration' => 2,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['adducteurs', 'fléchisseurs de hanche'],
                'equipment' => 'tapis',
                'category' => 'mobility',
                'estimated_calories' =>4,
                'instructions' => [
                    'À genoux, une jambe sur le côté',
                    'Bascule latérale pour étirer',
                    'Maintenez et respirez'
                ],
                'tips' => [
                    'Ne forcez pas l\'étirement'
                ]
            ],
            [
                'name' => 'Dynamic Pigeon',
                'body_part' => 'mobility',
                'description' => 'Étirement dynamique du pigeon pour les hanches.',
                'video_url' => 'https://i.imgur.com/ZJaVctz.mp4',
                'duration' => 2,
                'difficulty' => 'intermediate',
                'muscle_groups' => ['fléchisseurs de hanche', 'piriformis'],
                'equipment' => 'tapis',
                'category' => 'mobility',
                'estimated_calories' =>5,
                'instructions' => [
                    'Position du pigeon',
                    'Mouvement dynamique avant-arrière',
                    'Alternez les jambes'
                ],
                'tips' => [
                    'Écoutez votre corps'
                ]
            ],

            // KETTLEBELL EXERCISES
            [
                'name' => 'Double Kettlebell Front Squat',
                'body_part' => 'legs',
                'description' => 'Squat avec deux kettlebells en position frontale.',
                'video_url' => 'https://i.imgur.com/AKYFon6.mp4',
                'duration' => 2,
                'difficulty' => 'advanced',
                'muscle_groups' => ['quadriceps', 'fessiers', 'tronc'],
                'equipment' => '2 kettlebells',
                'category' => 'strength',
                'estimated_calories' =>12,
                'instructions' => [
                    'Kettlebells en position de rack',
                    'Squat en gardant le torse droit',
                    'Remontez en poussant sur les talons'
                ],
                'tips' => [
                    'Gardez les coudes hauts'
                ]
            ]
        ];

        // Insert exercises in batches for better performance
        $batches = array_chunk($exercises, 10);
        
        foreach ($batches as $batch) {
            foreach ($batch as $exercise) {
                Exercise::create($exercise);
            }
        }

        $this->command->info('✅ ' . count($exercises) . ' exercices créés avec succès !');
        
        // Display statistics
        $stats = [
            'Total' => count($exercises),
            'Chest' => count(array_filter($exercises, fn($e) => $e['body_part'] === 'chest')),
            'Back' => count(array_filter($exercises, fn($e) => $e['body_part'] === 'back')),
            'Legs' => count(array_filter($exercises, fn($e) => $e['body_part'] === 'legs')),
            'Arms' => count(array_filter($exercises, fn($e) => $e['body_part'] === 'arms')),
            'Shoulders' => count(array_filter($exercises, fn($e) => $e['body_part'] === 'shoulders')),
            'Abs' => count(array_filter($exercises, fn($e) => $e['body_part'] === 'abs')),
            'Cardio' => count(array_filter($exercises, fn($e) => $e['body_part'] === 'cardio')),
            'Mobility' => count(array_filter($exercises, fn($e) => $e['body_part'] === 'mobility')),
            'Flexibility' => count(array_filter($exercises, fn($e) => $e['body_part'] === 'flexibility')),
        ];
        
        $videosCount = count(array_filter($exercises, fn($e) => $e['video_url'] !== null));
        
        $this->command->info('📊 Répartition par partie du corps :');
        foreach ($stats as $part => $count) {
            $this->command->info("   - {$part}: {$count}");
        }
        $this->command->info("🎥 Exercices avec vidéos: {$videosCount}");
    }
}