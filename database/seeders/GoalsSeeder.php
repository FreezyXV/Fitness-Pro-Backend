<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Goal;
use Illuminate\Support\Carbon;
use App\Support\SystemUserResolver;

class GoalsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systemUser = SystemUserResolver::resolve();

        $goals = [
            [
                'title' => "Perdre 10 kg pour l'été",
                'description' => "Atteindre un poids plus sain en adoptant une alimentation équilibrée et une routine d'exercice régulière.",
                'category' => 'weight',
                'unit' => 'kg',
                'target_value' => 10,
                'target_date' => Carbon::now()->addDays(60)->toDateString(),
                'priority' => 2,
            ],
            [
                'title' => 'Courir 150 km ce mois',
                'description' => 'Améliorer mon endurance cardiovasculaire en courant régulièrement.',
                'category' => 'cardio',
                'unit' => 'km',
                'target_value' => 150,
                'target_date' => Carbon::now()->endOfMonth()->toDateString(),
                'priority' => 3,
            ],
            [
                'title' => 'Méditer 30 jours consécutifs',
                'description' => 'Développer une pratique de méditation quotidienne pour améliorer mon bien-être mental.',
                'category' => 'mental',
                'unit' => 'jours',
                'target_value' => 30,
                'priority' => 4,
            ],
            [
                'title' => 'Faire 12 000 pas par jour',
                'description' => "Maintenir un niveau d'activité quotidien élevé pour améliorer ma santé cardiovasculaire.",
                'category' => 'fitness',
                'unit' => 'jours',
                'target_value' => 30,
                'priority' => 3,
            ],
            [
                'title' => 'Développé couché : 100kg',
                'description' => 'Passer de 80kg à 100kg au développé couché avec une technique parfaite.',
                'category' => 'strength',
                'unit' => 'kg',
                'target_value' => 100,
                'priority' => 1,
            ],
            [
                'title' => 'Programme yoga quotidien',
                'description' => 'Intégrer 20 minutes de yoga chaque jour pour améliorer flexibilité et équilibre.',
                'category' => 'flexibility',
                'unit' => 'séances',
                'target_value' => 30,
                'priority' => 3,
            ],
        ];

        foreach ($goals as $goalData) {
            Goal::updateOrCreate(
                [
                    'user_id' => $systemUser->id,
                    'title' => $goalData['title'],
                ],
                array_merge($goalData, [
                    'user_id' => $systemUser->id,
                    'status' => 'not-started',
                    'current_value' => 0,
                ])
            );
        }

        $this->command->info('Created template goals for system user: ' . $systemUser->email);
    }
}
