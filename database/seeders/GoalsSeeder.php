<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Carbon;

class GoalsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user to associate goals with, or create one if none exists
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
                'email_verified_at' => now()
            ]);
        }

        $goals = [
            [
                'user_id' => $user->id,
                'title' => "Perdre 10 kg pour l'été",
                'description' => "Atteindre un poids plus sain en adoptant une alimentation équilibrée et une routine d'exercice régulière.",
                'category' => 'weight',
                'unit' => 'kg',
                'current_value' => 0,
                'target_value' => 10,
                'target_date' => Carbon::now()->addDays(60)->toDateString(),
                'status' => 'active',
                'created_at' => Carbon::now()->subDays(45),
                'updated_at' => Carbon::now()->subDays(45),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Courir 150 km ce mois',
                'description' => 'Améliorer mon endurance cardiovasculaire en courant régulièrement.',
                'category' => 'cardio',
                'unit' => 'km',
                'current_value' => 120,
                'target_value' => 150,
                'target_date' => Carbon::now()->endOfMonth()->toDateString(),
                'status' => 'active',
                'created_at' => Carbon::now()->startOfMonth(),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Méditer 30 jours consécutifs',
                'description' => 'Développer une pratique de méditation quotidienne pour améliorer mon bien-être mental.',
                'category' => 'mental',
                'unit' => 'jours',
                'current_value' => 30,
                'target_value' => 30,
                'target_date' => Carbon::now()->subDay()->toDateString(),
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(60),
                'updated_at' => Carbon::now()->subDay(),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Faire 12 000 pas par jour',
                'description' => "Maintenir un niveau d'activité quotidien élevé pour améliorer ma santé cardiovasculaire.",
                'category' => 'fitness',
                'unit' => 'jours',
                'current_value' => 25,
                'target_value' => 30,
                'target_date' => Carbon::now()->addDays(5)->toDateString(),
                'status' => 'active',
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDay(),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Développé couché : 100kg',
                'description' => 'Passer de 80kg à 100kg au développé couché avec une technique parfaite.',
                'category' => 'strength',
                'unit' => 'kg',
                'current_value' => 92,
                'target_value' => 100,
                'target_date' => Carbon::now()->addDays(45)->toDateString(),
                'status' => 'active',
                'created_at' => Carbon::now()->subDays(60),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Programme yoga quotidien',
                'description' => 'Intégrer 20 minutes de yoga chaque jour pour améliorer flexibilité et équilibre.',
                'category' => 'flexibility',
                'unit' => 'séances',
                'current_value' => 18,
                'target_value' => 30,
                'target_date' => Carbon::now()->addDays(12)->toDateString(),
                'status' => 'paused',
                'created_at' => Carbon::now()->subDays(18),
                'updated_at' => Carbon::now()->subDays(5),
            ],
        ];

        foreach ($goals as $goalData) {
            Goal::create($goalData);
        }

        $this->command->info('Created 6 sample goals for user: ' . $user->email);
    }
}