<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        $user = User::first();
        if ($categories->isEmpty() || !$user) return;

        foreach ($categories as $category) {
            $services = [
                [
                    'title' => 'Vérification des mises à jour de sécurité',
                    'category_id' => $category->id,
                    'status' => true,
                    'created_by' => $user->id,
                ],
                [
                    'title' => 'Monitoring des performances',
                    'category_id' => $category->id,
                    'status' => true,
                    'created_by' => $user->id,
                ],
                [
                    'title' => 'Nettoyage des logs',
                    'category_id' => $category->id,
                    'status' => true,
                    'created_by' => $user->id,
                ],
                [
                    'title' => 'Vérification des sauvegardes',
                    'category_id' => $category->id,
                    'status' => true,
                    'created_by' => $user->id,
                ],
            ];
            foreach ($services as $service) {
                Service::create($service);
            }
        }
    }
} 