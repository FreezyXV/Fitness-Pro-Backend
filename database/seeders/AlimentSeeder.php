<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Aliment;

class AlimentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Aliment::create([
            'name' => 'Apple',
            'category' => 'Fruit',
            'calories' => 52,
            'proteins' => 0.3,
            'carbohydrates' => 14,
            'fats' => 0.2,
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Red_Apple.jpg/1200px-Red_Apple.jpg',
            'unit' => '1 medium',
        ]);

        Aliment::create([
            'name' => 'Chicken Breast',
            'category' => 'Meat',
            'calories' => 165,
            'proteins' => 31,
            'carbohydrates' => 0,
            'fats' => 3.6,
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b1/Chicken_breast_on_white_background.jpg/1200px-Chicken_breast_on_white_background.jpg',
            'unit' => '100g',
        ]);

        Aliment::create([
            'name' => 'Broccoli',
            'category' => 'Vegetable',
            'calories' => 55,
            'proteins' => 3.7,
            'carbohydrates' => 11.2,
            'fats' => 0.6,
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/03/Broccoli_Rabe.jpg/1200px-Broccoli_Rabe.jpg',
            'unit' => '1 cup',
        ]);

        Aliment::create([
            'name' => 'Brown Rice',
            'category' => 'Grain',
            'calories' => 111,
            'proteins' => 2.6,
            'carbohydrates' => 23,
            'fats' => 0.9,
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7e/Brown_rice_unpolished.jpg/1200px-Brown_rice_unpolished.jpg',
            'unit' => '100g cooked',
        ]);

        Aliment::create([
            'name' => 'Salmon',
            'category' => 'Fish',
            'calories' => 208,
            'proteins' => 20,
            'carbohydrates' => 0,
            'fats' => 13,
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/39/Salmon_fillet.jpg/1200px-Salmon_fillet.jpg',
            'unit' => '100g',
        ]);
    }
}
