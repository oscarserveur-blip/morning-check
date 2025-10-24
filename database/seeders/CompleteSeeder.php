<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Category;
use App\Models\Service;
use App\Models\Check;
use App\Models\ServiceCheck;
use App\Models\Holiday;
use App\Models\Template;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CompleteSeeder extends Seeder
{
    public function run(): void
    {
        // Créer l'utilisateur admin
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        // Créer un template par défaut si besoin
        $template = Template::first() ?? Template::create([
            'name' => 'Template par défaut',
            'description' => 'Template initial',
            'type' => 'excel',
        ]);

        // Créer un client
        $client = Client::create([
            'label' => 'Client Test',
            'template_id' => $template->id,
            'check_time' => '09:00'
        ]);

        // Créer une catégorie
        $category = Category::create([
            'title' => 'Catégorie Test',
            'client_id' => $client->id,
            'status' => true,
            'created_by' => $user->id
        ]);

        // Créer des services
        $services = [
            [
                'title' => 'Vérification des mises à jour',
                'category_id' => $category->id,
                'status' => true,
                'created_by' => $user->id
            ],
            [
                'title' => 'Monitoring des performances',
                'category_id' => $category->id,
                'status' => true,
                'created_by' => $user->id
            ],
            [
                'title' => 'Nettoyage des logs',
                'category_id' => $category->id,
                'status' => true,
                'created_by' => $user->id
            ]
        ];

        foreach ($services as $serviceData) {
            Service::create($serviceData);
        }

        // Créer un check
        $check = Check::create([
            'date_time' => Carbon::now(),
            'client_id' => $client->id,
            'created_by' => $user->id,
            'statut' => 'pending'
        ]);

        // Créer les ServiceChecks
        $services = Service::where('category_id', $category->id)->get();
        foreach ($services as $service) {
            ServiceCheck::create([
                'check_id' => $check->id,
                'service_id' => $service->id,
                'statut' => 'pending',
                'etat' => 'pending'
            ]);
        }

        // Ajouter les jours fériés
        $currentYear = Carbon::now()->year;
        $holidays = [
            ['date' => "{$currentYear}-01-01", 'label' => 'Jour de l\'an'],
            ['date' => "{$currentYear}-04-01", 'label' => 'Lundi de Pâques'],
            ['date' => "{$currentYear}-05-01", 'label' => 'Fête du Travail'],
            ['date' => "{$currentYear}-05-08", 'label' => 'Victoire 1945'],
            ['date' => "{$currentYear}-05-09", 'label' => 'Ascension'],
            ['date' => "{$currentYear}-05-20", 'label' => 'Lundi de Pentecôte'],
            ['date' => "{$currentYear}-07-14", 'label' => 'Fête Nationale'],
            ['date' => "{$currentYear}-08-15", 'label' => 'Assomption'],
            ['date' => "{$currentYear}-11-01", 'label' => 'Toussaint'],
            ['date' => "{$currentYear}-11-11", 'label' => 'Armistice 1918'],
            ['date' => "{$currentYear}-12-25", 'label' => 'Noël'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }

        $this->command->info('Base de données complètement réinitialisée avec des données de test');
    }
} 