<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'ivanpetrov@mail.com'],
            [
                'name'     => 'Ivan Petrov',
                'password' => Hash::make('password123'),   // ← mot de passe clair
            ]
        );
    }
}
