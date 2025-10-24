<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'title' => 'Sécurité',
                'client_id' => 1,
                'category_pk' => null,
                'status' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Performance',
                'client_id' => 1,
                'category_pk' => null,
                'status' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Maintenance',
                'client_id' => 1,
                'category_pk' => null,
                'status' => true,
                'created_by' => 1
            ],
            [
                'title' => 'Sauvegarde',
                'client_id' => 1,
                'category_pk' => null,
                'status' => true,
                'created_by' => 1
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
} 